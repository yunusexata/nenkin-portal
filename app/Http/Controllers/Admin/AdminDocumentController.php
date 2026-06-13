<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Document;
use App\Models\DocumentUploadBatch;
use App\Models\DocumentUploadFailure;
use App\Jobs\ProcessDocumentBatch;
use App\Services\DocumentMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AdminDocumentController extends Controller
{
    /**
     * Display document upload form, including active reference data and recent batches.
     */
    public function index()
    {
        $adminId = auth()->id();
        $referenceClients = Cache::get("reference_clients_{$adminId}");

        $batches = DocumentUploadBatch::with(['uploader', 'documents.client'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('admin.documents.index', compact('batches', 'referenceClients'));
    }

    /**
     * Step 1: Upload Excel Reference file and do lookup.
     */
    public function uploadExcelReference(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ], [
            'excel_file.required' => 'Harap pilih file Excel.',
            'excel_file.mimes' => 'Format file Excel harus berupa .xlsx atau .xls.',
        ]);

        try {
            $adminId = auth()->id();

            // Read the Excel sheet directly as an array without making a new import class
            $array = Excel::toArray(new \App\Imports\ExcelImportBulk(), $request->file('excel_file'));
            $sheet = $array[0] ?? [];

            if (empty($sheet)) {
                return redirect()->back()->withErrors(['error' => 'File Excel kosong atau tidak terbaca.']);
            }

            $referenceClients = [];

            foreach ($sheet as $row) {
                // Handle heading row normalize (snake_case)
                $noJpn = $row['no_input_jpn'] ?? $row['no_input_jp'] ?? $row['no_jp'] ?? $row['noinputjpn'] ?? null;
                $name = $row['nama'] ?? $row['name'] ?? null;
                $dob = $row['tgl_lahir'] ?? $row['tanggal_lahir'] ?? $row['dob'] ?? null;

                if (!$noJpn && !$name) {
                    continue;
                }

                // Convert DOB if it is a serial Excel date number
                if (is_numeric($dob)) {
                    try {
                        $dob = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dob))->format('Y-m-d');
                    } catch (\Throwable $e) {
                        // Keep as is if conversion fails
                    }
                } elseif ($dob) {
                    try {
                        $dob = \Carbon\Carbon::parse($dob)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        // Keep as is
                    }
                }

                // Look up in database by unique no_jp (No Input JPN)
                $client = Client::where('no_jp', $noJpn)->first();

                $referenceClients[] = [
                    'no_jp' => $noJpn,
                    'name' => $name,
                    'dob' => $dob,
                    'found' => $client ? true : false,
                    'client_id' => $client ? $client->id : null,
                ];
            }

            if (empty($referenceClients)) {
                return redirect()->back()->withErrors(['error' => 'Tidak ada data klien yang valid ditemukan di file Excel. Pastikan header sesuai (NO INPUT JPN, NAMA, TGL LAHIR).']);
            }

            // Cache for 30 minutes
            Cache::put("reference_clients_{$adminId}", $referenceClients, now()->addMinutes(30));

            return redirect()->route('admin_upload_document.index')
                ->with('success', 'Excel referensi berhasil di-upload. Silakan tinjau daftar klien sebelum melanjutkannya.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal memproses file Excel: ' . $e->getMessage()]);
        }
    }

    /**
     * Clear active reference data cache.
     */
    public function clearReference()
    {
        $adminId = auth()->id();
        Cache::forget("reference_clients_{$adminId}");

        return redirect()->route('admin_upload_document.index')
            ->with('success', 'Referensi klien berhasil di-reset.');
    }

    /**
     * Step 2: Upload Document Files, run matching without uploading to Supabase,
     * and save preview data temporarily in Cache.
     */
    public function upload(Request $request, DocumentMatchingService $matchingService)
    {
        $request->validate([
            'ichijikin_files.*' => 'nullable|file|mimes:jpg,jpeg,png',
            'resi_files.*' => 'nullable|file|mimes:jpg,jpeg,png',
            'kwitansi_files.*' => 'nullable|file|mimes:jpg,jpeg,png',
        ], [
            'ichijikin_files.*.mimes' => 'Format file Ichijikin harus berupa jpg, jpeg, atau png.',
            'resi_files.*.mimes' => 'Format file Resi Transfer harus berupa jpg, jpeg, atau png.',
            'kwitansi_files.*.mimes' => 'Format file Kwitansi harus berupa jpg, jpeg, atau png.',
        ]);

        $adminId = auth()->id();
        $referenceClients = Cache::get("reference_clients_{$adminId}");

        if (!$referenceClients) {
            return redirect()->route('admin_upload_document.index')
                ->withErrors(['error' => 'Referensi klien dari Step 1 tidak ditemukan. Harap upload Excel terlebih dahulu.']);
        }

        $totalFiles = 0;
        $fileTypes = [
            'ichijikin' => 'ichijikin_files',
            'resi' => 'resi_files',
            'kwitansi' => 'kwitansi_files'
        ];

        foreach ($fileTypes as $inputKey) {
            if ($request->hasFile($inputKey)) {
                $totalFiles += count($request->file($inputKey));
            }
        }

        if ($totalFiles === 0) {
            return redirect()->back()->withErrors(['error' => 'Harap pilih minimal satu file dokumen untuk dicocokkan.']);
        }

        // Generate a temporary batch ID
        $tempBatchId = 'temp_' . Str::random(12);

        // Get subset of found client IDs from Step 1 to restrict name matching space
        $allowedClientIds = collect($referenceClients)
            ->where('found', true)
            ->pluck('client_id')
            ->filter()
            ->toArray();

        $matchedFiles = [];
        $failedFiles = [];

        foreach ($fileTypes as $type => $inputKey) {
            if ($request->hasFile($inputKey)) {
                foreach ($request->file($inputKey) as $file) {
                    $originalName = $file->getClientOriginalName();

                    // Standardize local filename
                    $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME), '_') . '_' . time() . '.' . $file->getClientOriginalExtension();

                    // Save to local private temp folder
                    $tempPath = "tmp_uploads/pending_{$adminId}_{$tempBatchId}/{$type}/{$safeName}";
                    Storage::disk('local')->putFileAs("tmp_uploads/pending_{$adminId}_{$tempBatchId}/{$type}", $file, $safeName);

                    // Run the name matching engine with document type specified
                    $matchResult = $matchingService->matchFilename($originalName, $allowedClientIds, $type);

                    // Determine classification
                    if ($matchResult['status'] === 'matched') {
                        $matchedFiles[] = [
                            'original_filename' => $originalName,
                            'temp_path' => $tempPath,
                            'folder_type' => $type,
                            'client_id' => $matchResult['matched_client']->id,
                            'client_name' => $matchResult['matched_client']->name,
                            'confidence' => $matchResult['confidence'],
                            'manually_assigned' => false,
                            'status' => 'matched',
                        ];
                    } else {
                        // For failed, low_confidence, and ambiguous files, serialize Eloquent models into plain primitive arrays
                        // to avoid "__PHP_Incomplete_Class" serialization issues when stored in Cache.
                        $candidatesArray = [];
                        if (!empty($matchResult['candidates'])) {
                            foreach ($matchResult['candidates'] as $c) {
                                $candidatesArray[] = [
                                    'id' => $c->id,
                                    'name' => $c->name,
                                    'no_jp' => $c->no_jp,
                                    'dob' => $c->dob instanceof \Carbon\Carbon ? $c->dob->format('Y-m-d') : (is_string($c->dob) ? $c->dob : ''),
                                ];
                            }
                        }

                        $bestCandidateArray = null;
                        if (!empty($matchResult['best_candidate'])) {
                            $bc = $matchResult['best_candidate'];
                            $bestCandidateArray = [
                                'id' => $bc->id,
                                'name' => $bc->name,
                                'no_jp' => $bc->no_jp,
                                'dob' => $bc->dob instanceof \Carbon\Carbon ? $bc->dob->format('Y-m-d') : (is_string($bc->dob) ? $bc->dob : ''),
                            ];
                        }

                        $failedFiles[] = [
                            'original_filename' => $originalName,
                            'temp_path' => $tempPath,
                            'folder_type' => $type,
                            'status' => $matchResult['status'],
                            'confidence' => $matchResult['confidence'],
                            'matched_client' => null,
                            'client_id' => null,
                            'client_name' => null,
                            'candidates' => $candidatesArray,
                            'best_candidate' => $bestCandidateArray,
                            'manually_assigned' => false,
                        ];
                    }
                }
            }
        }

        // Compute missing documents list (Sub-kondisi 2) - matched-only files are considered "Ada"
        $missingDocuments = $this->computeMissingDocuments($referenceClients, $matchedFiles);

        // Save preview state to Cache with 30 minutes TTL
        $previewData = [
            'temp_batch_id' => $tempBatchId,
            'matched_files' => $matchedFiles,
            'failed_files' => $failedFiles,
            'missing_documents' => $missingDocuments,
            'reference_clients' => $referenceClients
        ];

        Cache::put("matching_preview_{$adminId}_{$tempBatchId}", $previewData, now()->addMinutes(30));

        return redirect()->route('admin.documents.previewMatching', $tempBatchId);
    }

    /**
     * Preview Results of matching with Section A & Section B (Sub-kondisi 1 & 2).
     */
    public function previewMatching($tempBatchId)
    {
        $adminId = auth()->id();
        $previewData = Cache::get("matching_preview_{$adminId}_{$tempBatchId}");

        if (!$previewData) {
            return redirect()->route('admin_upload_document.index')
                ->withErrors(['error' => 'Data preview tidak ditemukan atau telah kedaluwarsa. Harap ulangi upload dokumen.']);
        }

        $matchedFiles = $previewData['matched_files'];
        $failedFiles = $previewData['failed_files'];
        $missingDocuments = $previewData['missing_documents'] ?? [];

        return view('admin.documents.preview-matching', compact('tempBatchId', 'matchedFiles', 'failedFiles', 'missingDocuments'));
    }

    /**
     * Manually assign failed file in Preview Section B. Handles failed, low_confidence, and ambiguous.
     */
    public function assignPreviewFailure(Request $request)
    {
        $request->validate([
            'temp_batch_id' => 'required',
            'original_filename' => 'required',
            'client_id' => 'required|exists:clients,id',
        ]);

        $adminId = auth()->id();
        $tempBatchId = $request->temp_batch_id;
        $originalFilename = $request->original_filename;
        $clientId = $request->client_id;

        $previewData = Cache::get("matching_preview_{$adminId}_{$tempBatchId}");

        if (!$previewData) {
            return redirect()->back()->withErrors(['error' => 'Data preview tidak ditemukan atau telah kedaluwarsa.']);
        }

        $failedFiles = $previewData['failed_files'];
        $matchedFiles = $previewData['matched_files'];

        $targetIndex = null;
        foreach ($failedFiles as $index => $file) {
            if ($file['original_filename'] === $originalFilename) {
                $targetIndex = $index;
                break;
            }
        }

        if ($targetIndex === null) {
            return redirect()->back()->withErrors(['error' => 'File tidak ditemukan di daftar kegagalan.']);
        }

        $client = Client::find($clientId);
        $fileToMove = $failedFiles[$targetIndex];

        // Move to matched files
        $fileToMove['client_id'] = $client->id;
        $fileToMove['client_name'] = $client->name;
        $fileToMove['manually_assigned'] = true;
        $fileToMove['status'] = 'matched';

        $matchedFiles[] = $fileToMove;

        // Remove from failed list
        array_splice($failedFiles, $targetIndex, 1);

        // Recompute missing documents list based on updated matched files
        $missingDocuments = $this->computeMissingDocuments($previewData['reference_clients'] ?? [], $matchedFiles);

        // Update preview state in cache
        $previewData['failed_files'] = $failedFiles;
        $previewData['matched_files'] = $matchedFiles;
        $previewData['missing_documents'] = $missingDocuments;
        Cache::put("matching_preview_{$adminId}_{$tempBatchId}", $previewData, now()->addMinutes(30));

        return redirect()->back()->with('success', "File \"{$originalFilename}\" berhasil dipindahkan dan di-resolve ke klien {$client->name}.");
    }

    /**
     * Confirm results, save batch and failures to database, and dispatch background upload job.
     */
    public function confirmAndUpload(Request $request)
    {
        $request->validate([
            'temp_batch_id' => 'required',
        ]);

        $adminId = auth()->id();
        $tempBatchId = $request->temp_batch_id;

        $previewData = Cache::get("matching_preview_{$adminId}_{$tempBatchId}");

        if (!$previewData) {
            return redirect()->route('admin_upload_document.index')
                ->withErrors(['error' => 'Data preview tidak ditemukan atau telah kedaluwarsa. Harap ulangi pemrosesan.']);
        }

        $matchedFiles = $previewData['matched_files'];
        $failedFiles = $previewData['failed_files'];
        $totalFiles = count($matchedFiles) + count($failedFiles);

        if ($totalFiles === 0) {
            return redirect()->route('admin_upload_document.index')
                ->withErrors(['error' => 'Tidak ada file untuk di-upload.']);
        }

        // 1. Create final database record for the Batch
        $batch = DocumentUploadBatch::create([
            'uploaded_by' => $adminId,
            'total_files' => $totalFiles,
            'matched_files' => 0,
            'failed_files' => 0,
            'status' => 'processing',
        ]);

        // 2. Prepare failed/unassigned files data, write to DB directly, and delete local temp files immediately
        foreach ($failedFiles as $file) {
            $oldPath = $file['temp_path'];

            // Delete the local temp file immediately
            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }

            // Extract best suggestion details for database failures recording
            $bestMatchName = $file['best_candidate']['name'] ?? null;
            $bestMatchScore = $file['confidence'] ?? null;

            $reason = "Nama tidak cocok. Kecocokan tertinggi: \"" . ($bestMatchName ?? 'N/A') . "\" dengan skor " . number_format($bestMatchScore ?? 0.0, 3) . " < 0.85";

            DocumentUploadFailure::create([
                'batch_id' => $batch->id,
                'original_filename' => $file['original_filename'],
                'folder_type' => $file['folder_type'],
                'failure_reason' => $reason,
                'best_match_name' => $bestMatchName,
                'best_match_score' => $bestMatchScore,
                'resolved_client_id' => null,
                'resolved_at' => null,
            ]);
        }

        $filesDataForQueue = [];

        // 3. Prepare matched files data and move temp local files to batch directory
        foreach ($matchedFiles as $file) {
            $oldPath = $file['temp_path'];
            $filename = basename($oldPath);
            $newPath = "tmp_uploads/{$batch->id}/{$file['folder_type']}/{$filename}";

            // Move locally
            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->move($oldPath, $newPath);
            }

            $filesDataForQueue[] = [
                'original_name' => $file['original_filename'],
                'temp_path' => $newPath,
                'type' => $file['folder_type'],
                'matched' => true,
                'client_id' => $file['client_id'],
                'confidence' => $file['confidence'],
                'manually_assigned' => $file['manually_assigned'],
            ];
        }

        // 4. Dispatch the updated ProcessDocumentBatch Job (only with matched/assigned files)
        ProcessDocumentBatch::dispatch($batch->id, $filesDataForQueue);

        // 5. Clean up local draft folders and Cache
        Storage::disk('local')->deleteDirectory("tmp_uploads/pending_{$adminId}_{$tempBatchId}");
        Cache::forget("matching_preview_{$adminId}_{$tempBatchId}");
        Cache::forget("reference_clients_{$adminId}");

        return redirect()->route('admin.documents.batchDetail', $batch->id)
            ->with('success', "Batch upload #{$batch->id} berhasil dikonfirmasi. Proses upload dan perekaman log kegagalan sedang berjalan di background.");
    }

    /**
     * Display detail of a specific upload batch.
     */
    public function batchDetail($id)
    {
        $batch = DocumentUploadBatch::with('uploader')->findOrFail($id);

        $documents = Document::with('client.documents')
            ->where('batch_id', $id)
            ->get();

        $failures = DocumentUploadFailure::with('resolvedClient')
            ->where('batch_id', $id)
            ->get();

        return view('admin.documents.batch-detail', compact('batch', 'documents', 'failures'));
    }

    /**
     * Search clients for AJAX manual resolution dropdown, restricted by SOT client list if temp_batch_id is provided.
     */
    public function searchClients(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $adminId = auth()->id();
        $tempBatchId = $request->get('temp_batch_id');
        $allowedClientIds = null;

        if ($tempBatchId) {
            $previewData = Cache::get("matching_preview_{$adminId}_{$tempBatchId}");
            if ($previewData && !empty($previewData['reference_clients'])) {
                $allowedClientIds = collect($previewData['reference_clients'])
                    ->where('found', true)
                    ->pluck('client_id')
                    ->filter()
                    ->toArray();
            }
        }

        $clientsQuery = Client::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('no_jp', 'like', "%{$query}%");
        });

        if ($allowedClientIds !== null) {
            $clientsQuery->whereIn('id', $allowedClientIds);
        }

        $clients = $clientsQuery->limit(10)->get(['id', 'name', 'no_jp']);

        return response()->json($clients);
    }

    /**
     * Resolve unmatched file by assigning it to a client manually.
     */
    public function resolveFailure(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id'
        ]);

        $failure = DocumentUploadFailure::findOrFail($id);
        $client = Client::findOrFail($request->client_id);

        $failuresPath = "failures/{$failure->batch_id}/{$failure->original_filename}";
        $targetPath = "documents/{$client->id}/{$failure->folder_type}/{$failure->original_filename}";

        if (!Storage::disk('supabase')->exists($failuresPath)) {
            return redirect()->back()->withErrors(['error' => 'File kegagalan tidak ditemukan di cloud storage.']);
        }

        // Check duplicate document of the same type for this client
        $existingDoc = Document::where('client_id', $client->id)
            ->where('type', $failure->folder_type)
            ->first();

        if ($existingDoc) {
            // Delete old file
            if (Storage::disk('supabase')->exists($existingDoc->bucket_path)) {
                Storage::disk('supabase')->delete($existingDoc->bucket_path);
            }

            // Update database record
            $existingDoc->update([
                'original_filename' => $failure->original_filename,
                'bucket_path' => $targetPath,
                'match_confidence' => 1.000, // Manual resolution is always 1.0 match
                'batch_id' => $failure->batch_id,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);
        } else {
            // Create new record
            Document::create([
                'client_id' => $client->id,
                'type' => $failure->folder_type,
                'original_filename' => $failure->original_filename,
                'bucket_path' => $targetPath,
                'match_confidence' => 1.000,
                'batch_id' => $failure->batch_id,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);
        }

        // Move file from failures/ to documents/ in Supabase
        Storage::disk('supabase')->put($targetPath, Storage::disk('supabase')->get($failuresPath));
        Storage::disk('supabase')->delete($failuresPath);

        // Update failure record to mark as resolved
        $failure->update([
            'resolved_client_id' => $client->id,
            'resolved_at' => now(),
        ]);

        // Update batch counters
        $batch = DocumentUploadBatch::findOrFail($failure->batch_id);
        $matchedCount = $batch->matched_files + 1;
        $failedCount = max(0, $batch->failed_files - 1);

        $status = 'completed';
        if ($failedCount > 0 && $matchedCount > 0) {
            $status = 'partial_failed';
        } elseif ($failedCount > 0 && $matchedCount == 0) {
            $status = 'failed';
        }

        $batch->update([
            'matched_files' => $matchedCount,
            'failed_files' => $failedCount,
            'status' => $status
        ]);

        return redirect()->back()->with('success', "File \"{$failure->original_filename}\" berhasil di-resolve ke klien {$client->name}.");
    }

    /**
     * Download CSV Failure Log for a specific batch.
     */
    public function exportFailures($batchId)
    {
        $failures = DocumentUploadFailure::where('batch_id', $batchId)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"failures-batch-{$batchId}.csv\"",
        ];

        $callback = function () use ($failures) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Original Filename', 'Folder Type', 'Failure Reason', 'Best Match Suggestion', 'Best Match Score', 'Status']);

            foreach ($failures as $failure) {
                fputcsv($file, [
                    $failure->original_filename,
                    ucfirst($failure->folder_type),
                    $failure->failure_reason,
                    $failure->best_match_name ?? 'Tidak ada saran',
                    $failure->best_match_score ? number_format($failure->best_match_score, 3) : 'N/A',
                    $failure->resolved_at ? 'Resolved' : 'Pending',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Private Helper: Iterate reference clients (found in DB) and check document availability
     * for 'ichijikin', 'resi', and 'kwitansi'. Only matchedFiles (auto-matched or manually assigned)
     * are counted as present ("Ada"). If any of the three is missing, record in array.
     */
    private function computeMissingDocuments($referenceClients, $matchedFiles)
    {
        if (empty($referenceClients)) {
            return [];
        }

        $missingDocuments = [];
        $foundReferenceClients = collect($referenceClients)->where('found', true);

        foreach ($foundReferenceClients as $refClient) {
            $clientId = $refClient['client_id'];

            // Check if there is any file inside matchedFiles for each category.
            // Under new requirements, $matchedFiles contains ONLY matched status files (auto-matched or manually assigned).
            $hasIchijikin = collect($matchedFiles)
                ->where('client_id', $clientId)
                ->where('folder_type', 'ichijikin')
                ->isNotEmpty();

            $hasResi = collect($matchedFiles)
                ->where('client_id', $clientId)
                ->where('folder_type', 'resi')
                ->isNotEmpty();

            $hasKwitansi = collect($matchedFiles)
                ->where('client_id', $clientId)
                ->where('folder_type', 'kwitansi')
                ->isNotEmpty();

            // If any of the three documents is missing, add to missing documents list
            if (!$hasIchijikin || !$hasResi || !$hasKwitansi) {
                $missingDocuments[] = [
                    'client_id' => $clientId,
                    'name' => $refClient['name'],
                    'no_jp' => $refClient['no_jp'],
                    'ichijikin' => $hasIchijikin,
                    'resi' => $hasResi,
                    'kwitansi' => $hasKwitansi,
                ];
            }
        }

        return $missingDocuments;
    }

    /**
     * Download client documents (ichijikin, resi, kwitansi) packaged in a ZIP file.
     */
    public function downloadZip(Client $client)
    {
        if (!class_exists('\ZipArchive')) {
            return redirect()->back()->with('error', 'Ekstensi ZipArchive tidak tersedia di server ini.');
        }

        $client->loadMissing('documents');

        $ichijikinDoc = $client->documents->where('type', 'ichijikin')->first();
        $resiDoc = $client->documents->where('type', 'resi')->first();
        $kwitansiDoc = $client->documents->where('type', 'kwitansi')->first();

        if (!$ichijikinDoc || !$ichijikinDoc->bucket_path) {
            return redirect()->back()->with('error', 'Dokumen Ichijikin untuk client ini belum tersedia.');
        }
        if (!$resiDoc || !$resiDoc->bucket_path) {
            return redirect()->back()->with('error', 'Dokumen Resi Transfer untuk client ini belum tersedia.');
        }
        if (!$kwitansiDoc || !$kwitansiDoc->bucket_path) {
            return redirect()->back()->with('error', 'Dokumen Kwitansi untuk client ini belum tersedia.');
        }

        $disk = Storage::disk('supabase');

        if (!$disk->exists($ichijikinDoc->bucket_path)) {
            return redirect()->back()->with('error', 'File Ichijikin tidak ditemukan di penyimpanan awan.');
        }
        if (!$disk->exists($resiDoc->bucket_path)) {
            return redirect()->back()->with('error', 'File Resi Transfer tidak ditemukan di penyimpanan awan.');
        }
        if (!$disk->exists($kwitansiDoc->bucket_path)) {
            return redirect()->back()->with('error', 'File Kwitansi tidak ditemukan di penyimpanan awan.');
        }

        try {
            $zip = new \ZipArchive();
            $zipFileName = 'Dokumen_' . Str::slug($client->name) . '_' . now()->format('Ymd') . '.zip';
            $tempDir = storage_path('app/temp');
            
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $zipPath = $tempDir . '/' . $zipFileName;

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return redirect()->back()->with('error', 'Gagal membuat file ZIP di server.');
            }

            $ichijikinExt = pathinfo($ichijikinDoc->original_filename, PATHINFO_EXTENSION) ?: 'jpg';
            $resiExt = pathinfo($resiDoc->original_filename, PATHINFO_EXTENSION) ?: 'jpg';
            $kwitansiExt = pathinfo($kwitansiDoc->original_filename, PATHINFO_EXTENSION) ?: 'jpg';

            $clientNameClean = str_replace(['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>', '.'], '', $client->name);

            $ichijikinName = "Ichijikin_" . $clientNameClean . "." . $ichijikinExt;
            $resiName = "Resi_" . $clientNameClean . "." . $resiExt;
            $kwitansiName = "Kwitansi_" . $clientNameClean . "." . $kwitansiExt;

            $zip->addFromString($ichijikinName, $disk->get($ichijikinDoc->bucket_path));
            $zip->addFromString($resiName, $disk->get($resiDoc->bucket_path));
            $zip->addFromString($kwitansiName, $disk->get($kwitansiDoc->bucket_path));

            $zip->close();

            return response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            if (isset($zipPath) && file_exists($zipPath)) {
                @unlink($zipPath);
            }
            logger()->error("Failed to generate ZIP", ['exception' => $e->getMessage(), 'client_id' => $client->id]);
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat membuat file ZIP: ' . $e->getMessage());
        }
    }
}
