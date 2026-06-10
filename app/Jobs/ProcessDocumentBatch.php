<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentUploadBatch;
use App\Models\DocumentUploadFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessDocumentBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param int $batchId
     * @param array $filesData Array of pre-matched file results
     */
    public function __construct(
        protected int $batchId,
        protected array $filesData
    ) {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        $batch = DocumentUploadBatch::find($this->batchId);
        if (!$batch) {
            return;
        }

        $matchedCount = 0;
        $failedCount = DocumentUploadFailure::where('batch_id', $this->batchId)->count();

        try {
            foreach ($this->filesData as $file) {
                $tempPath = $file['temp_path'];
                $originalName = $file['original_name'];
                $type = $file['type'];
                $isMatched = $file['matched'];

                try {
                    // Check if file still exists in local storage
                    if (!Storage::disk('local')->exists($tempPath)) {
                        throw new Exception("File temporary tidak ditemukan di lokal.");
                    }

                    if ($isMatched) {
                        $clientId = $file['client_id'];
                        $confidence = $file['confidence'] ?? 1.000;
                        
                        $targetPath = "documents/{$clientId}/{$type}/{$originalName}";

                        // Check if a document of this type already exists for this client
                        $existingDoc = Document::where('client_id', $clientId)
                            ->where('type', $type)
                            ->first();

                        if ($existingDoc) {
                            // Delete the old file from Supabase Storage
                            if (Storage::disk('supabase')->exists($existingDoc->bucket_path)) {
                                Storage::disk('supabase')->delete($existingDoc->bucket_path);
                            }

                            // Update existing record
                            $existingDoc->update([
                                'original_filename' => $originalName,
                                'bucket_path' => $targetPath,
                                'match_confidence' => $confidence,
                                'batch_id' => $this->batchId,
                                'uploaded_by' => $batch->uploaded_by,
                                'uploaded_at' => now(),
                            ]);
                        } else {
                            // Create new record
                            Document::create([
                                'client_id' => $clientId,
                                'type' => $type,
                                'original_filename' => $originalName,
                                'bucket_path' => $targetPath,
                                'match_confidence' => $confidence,
                                'batch_id' => $this->batchId,
                                'uploaded_by' => $batch->uploaded_by,
                                'uploaded_at' => now(),
                            ]);
                        }

                        // Upload the file to Supabase Storage with explicit logging for Step B & C
                        logger()->info('PRE-UPLOAD CHECK', [
                            'bucket_path' => $targetPath,
                            'local_file_exists' => Storage::disk('local')->exists($tempPath),
                            'local_file_size' => Storage::disk('local')->exists($tempPath) 
                                ? Storage::disk('local')->size($tempPath) 
                                : 'FILE NOT FOUND',
                            'disk_config' => config('filesystems.disks.supabase.endpoint'),
                        ]);

                        try {
                            $uploadResult = Storage::disk('supabase')->put($targetPath, Storage::disk('local')->get($tempPath));
                            logger()->info('POST-UPLOAD RESULT', ['result' => $uploadResult]);
                        } catch (\Throwable $e) {
                            logger()->error('UPLOAD EXCEPTION', [
                                'message' => $e->getMessage(),
                                'code' => $e->getCode(),
                            ]);
                            throw $e; // re-throw agar job masuk failed_jobs
                        }

                        $matchedCount++;
                    } else {
                        // Matching failed, log failure and DO NOT upload to Supabase
                        $bestMatchName = $file['best_match_name'] ?? null;
                        $bestMatchScore = $file['best_match_score'] ?? null;
                        
                        $reason = "Nama tidak cocok. Kecocokan tertinggi: \"" . ($bestMatchName ?? 'N/A') . "\" dengan skor " . number_format($bestMatchScore ?? 0.0, 3) . " < 0.85";

                        DocumentUploadFailure::create([
                            'batch_id' => $this->batchId,
                            'original_filename' => $originalName,
                            'folder_type' => $type,
                            'failure_reason' => $reason,
                            'best_match_name' => $bestMatchName,
                            'best_match_score' => $bestMatchScore,
                            'resolved_client_id' => null,
                            'resolved_at' => null,
                        ]);

                        $failedCount++;
                    }
                } catch (\Throwable $fileEx) {
                    logger()->error("Gagal memproses file {$originalName} di batch {$this->batchId}: " . $fileEx->getMessage());
                    
                    // Increment failed count and record as failure
                    DocumentUploadFailure::create([
                        'batch_id' => $this->batchId,
                        'original_filename' => $originalName,
                        'folder_type' => $type,
                        'failure_reason' => "Error sistem: " . $fileEx->getMessage(),
                        'best_match_name' => null,
                        'best_match_score' => null,
                        'resolved_client_id' => null,
                        'resolved_at' => null,
                    ]);
                    $failedCount++;
                } finally {
                    // Local temporary file cleanup
                    if (Storage::disk('local')->exists($tempPath)) {
                        Storage::disk('local')->delete($tempPath);
                    }
                }
            }
        } catch (\Throwable $batchEx) {
            logger()->error("Kritikal error pada Job Batch {$this->batchId}: " . $batchEx->getMessage());
        } finally {
            // Cleanup the temporary directory completely
            $batchTempDir = "tmp_uploads/{$this->batchId}";
            if (Storage::disk('local')->exists($batchTempDir)) {
                Storage::disk('local')->deleteDirectory($batchTempDir);
            }

            // Determine final status
            $status = 'completed';
            if ($failedCount > 0 && $matchedCount > 0) {
                $status = 'partial_failed';
            } elseif ($failedCount > 0 && $matchedCount == 0) {
                $status = 'failed';
            }

            // Update batch counters and status
            $batch->update([
                'matched_files' => $matchedCount,
                'failed_files' => $failedCount,
                'status' => $status,
                'completed_at' => now(),
            ]);
        }
    }
}
