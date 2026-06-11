<?php

namespace App\Livewire\MasterClient;

use App\Helpers\Alert;
use App\Helpers\ExportHelper;
use App\Imports\ExcelImportBulk;
use App\Models\Client\Client;
use App\Repositories\Client\ClientRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportModal extends Component
{
    use WithFileUploads;

    public $input_file;
    public $batch_name;

    // Import Pipeline
    public $previewBulkStatusRows;
    public $errorBulkStatusRows = [];

    public function mount() {}
    // IMPORT PIPELINE
    public function updatedInputFile()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300); // 5 minutes

        consoleLog($this, 'con');
        $import = new ExcelImportBulk();
        Excel::import($import, $this->input_file);

        $this->previewBulkStatusRows = [];
        $this->errorBulkStatusRows = [];

        if ($this->input_file) {
            $originalName = $this->input_file->getClientOriginalName();
            $this->batch_name = pathinfo($originalName, PATHINFO_FILENAME);
        }

        $d = [];
        foreach ($import->rows as $index => $row) {
            $d = [

                'no_jp' => $row['no_input_jpn'],
                'name' => $row['nama'],
                'dob' => $row['tgl_lahir'],
                'phone' => $row['no_telp'],
                'raw_data' => $row,
                'facebook' => $row['nama_fb'],
                'instagram' => $row['nama_ig'],
                'pic' => $row['pic'],
                'lpk' => $row['lpk'],
                'note' => $row['note'],
            ];
            $validator = Validator::make($d, [
                'no_jp' => 'required',
                'name' => 'required',
                'dob' => 'required',
                'pic' => 'required',
            ], [
                'no_jp.required' => 'No Input Japan harus di isi',
                'name.required' => 'Nama harus di isi',
                'dob.required' => 'Tgl Lahir harus di isi',
                'phone.required' => 'No Telp harus di isi',
                'pic.required' => 'No Input Japan harus di isi',
            ]);

            $this->previewBulkStatusRows[] = [
                'data' => $d,
                'error' => $validator->errors()->toArray()
            ];

            if ($validator->fails()) {
                $this->errorBulkStatusRows[] = $index;
            }
        }
    }

    public function closeImportBulkStatusModal()
    {
        $this->reset('input_file');
        $this->previewBulkStatusRows = [];
        $this->errorBulkStatusRows = [];
    }

    public function storeImport()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300); // 5 minutes

        $this->validate([
            'batch_name' => 'required|string|max:100',
        ], [
            'batch_name.required' => 'Nama Batch wajib diisi',
            'batch_name.string' => 'Nama Batch harus berupa teks',
            'batch_name.max' => 'Nama Batch maksimal 100 karakter',
        ]);

        try {
            DB::beginTransaction();

            $extension = $this->input_file
                ->extension();

            $fileName = $this->input_file->getClientOriginalName() . '.' . $extension;
            $disk = env('DEFAULT_STORE_DISK', 'private');

            $storedName = Str::uuid() . '.' . $this->input_file->extension();
            $filePath =   "import_excel_jp";

            $path = Storage::disk($disk)->putFileAs(
                $filePath,
                $this->input_file,
                $storedName,
                [
                    'visibility' => 'private',
                ]
            );

            foreach ($this->previewBulkStatusRows as $key => $value) {

                if (!$value['error']) {
                    $phone = trim((string) ($value['data']['phone'] ?? ''));

                    $validatedData = [

                        'no_jp' => $value['data']['no_jp'],
                        'name' => $value['data']['name'],
                        'name_normalized' => str($value['data']['name'])->lower()->replace(' ', '-'),
                        'dob' => $value['data']['dob'],
                        'phone' => (strlen($phone) < 7) ? null : $phone,
                        'raw_data' => json_encode($value['data'], true),
                        'facebook' => $value['data']['facebook'],
                        'instagram' => $value['data']['instagram'],
                        'pic' => $value['data']['pic'],
                        'lpk' => $value['data']['lpk'],
                        'note' => $value['data']['note'],

                        'source_file_name' => $this->batch_name,
                        'source_file_disk' => $disk,
                        'source_file_path' => $path,
                        'imported_at' => now(),

                        'claim_type' => Client::CLAIM_TYPE_NORMAL,
                        'status' => Client::STATUS_PENDING,

                    ];
                    // Client::firstOrCreate(
                    Client::updateOrCreate(
                        ['no_jp' => $value['data']['no_jp']],
                        $validatedData
                    );
                }
            }

            $path = $this->input_file->getRealPath();
            unlink($path);
            DB::commit();
            $this->dispatch('datatable-refresh');
            $this->dispatch('onSuccessImportModal');
            $this->closeModal();

            Alert::information($this, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::fail($this, "Gagal", $e->getMessage());
        }
    }

    #[On('modal-filter')]
    public function modalFilter($filter)
    {
        foreach ($filter as $key => $value) {
            $this->$key = $value;
        }
    }

    #[On('export')]
    public function export($type)
    {
        $fileName = "Data Client " . Carbon::now()->format('Y-m-d H:i:s');
        return ExportHelper::export(
            $type,
            $fileName,
            ClientRepository::getMagicLink($this->batch_name),
            'app.master-client.export',
            [
                'title' => 'Data Client',
                'type' => $type,
            ],
            [
                'size' => 'legal',
                'orientation' => 'landscape',
            ]
        );
    }

    public function closeModal()
    {
        $this->reset('input_file');
        $this->previewBulkStatusRows = [];
        $this->errorBulkStatusRows = [];
    }

    public function render()
    {
        return view('livewire.master-client.import-modal');
    }
}
