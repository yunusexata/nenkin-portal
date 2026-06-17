<?php

namespace App\Livewire\MasterClientRegistered;

use App\Helpers\Alert;
use App\Helpers\ExportHelper;
use App\Helpers\PermissionHelper;
use App\Models\Client\Client;
use App\Models\Client\ClientConfirmation;
use App\Models\GensenForm\GensenFormLink;
use App\Repositories\Account\UserRepository;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Exata\ExataFormCandidateRepository;
use App\Repositories\GensenForm\GensenFormLinkRepository;
use App\Traits\Livewire\WithDatatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\On;
use Livewire\Component;


class Datatable extends Component
{
    use WithDatatable;

    public $isCanUpdate;
    public $isCanDelete;
    public $isCanUpdateBookingTime;
    public $isCanUpdateDetail;

    public $isAdmin = false;
    public $status;
    public $selectedBatch = 'all';
    public $batches = [];
    public $bulkPic;

    public $batch_name;
    public $filter_claim_type;

    // Delete Dialog
    public $targetDeleteId;

    public function selectBatch($batchName)
    {
        $this->selectedBatch = $batchName;
        $this->resetPage();

        $this->dispatch('modal-filter', [
            'batch_name' => $batchName,
        ]);
        if ($batchName !== 'all') {
            $this->dispatch('openBatchModal', batchName: $batchName);
        }
    }

    public function loadBatches()
    {
        $this->batches = \App\Models\Client\Client::select('source_file_name as name', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereNotNull('source_file_name')
            ->groupBy('source_file_name')
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('MAX(imported_at)'))
            ->get()
            ->toArray();
    }

    public function onMount()
    {
        $authUser = UserRepository::authenticatedUser();
        $this->isCanUpdate = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_IMPORT_DATA_JEPANG, PermissionHelper::TYPE_UPDATE));
        $this->isCanDelete = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_IMPORT_DATA_JEPANG, PermissionHelper::TYPE_DELETE));
        $this->loadBatches();
        $this->isAdmin = auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN);
        $this->bulkPic = auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN) ? null : auth()->user()->pic;
    }

    #[On('on-delete-dialog-confirm')]
    public function onDialogDeleteConfirm()
    {
        if (!$this->isCanDelete || $this->targetDeleteId == null) {
            return;
        }

        ClientRepository::delete($this->targetDeleteId);
        Alert::success($this, 'Berhasil', 'Data berhasil dihapus');
    }

    #[On('on-delete-dialog-cancel')]
    public function onDialogDeleteCancel()
    {
        $this->targetDeleteId = null;
    }

    public function showDeleteDialog($id)
    {
        $this->targetDeleteId = $id;

        Alert::confirmation(
            $this,
            Alert::ICON_QUESTION,
            "Hapus Data",
            "Apakah Anda Yakin Ingin Menghapus Data Ini ?",
            "on-delete-dialog-confirm",
            "on-delete-dialog-cancel",
            "Hapus",
            "Batal",
        );
    }

    #[On('refresh-table')]
    public function refreshTable()
    {
        $this->loadBatches();
        $this->resetPage();
    }

    #[On('datatable-refresh')]
    public function datatableRefresh()
    {
        $this->loadBatches();
        $this->resetPage();
    }


    public function getColumns(): array
    {
        return [
            // [
            //     'name' => 'Action',
            //     'sortable' => false,
            //     'searchable' => false,
            //     'render' => function ($item) {
            //         $editHtml = "";

            //         $id = Crypt::encrypt($item->id);
            //         if ($this->isCanUpdate) {
            //             // $editUrl = route('gensen_form_link.edit', $id);
            //             $editUrl = '';
            //             $editHtml = "<div class='col-auto'>
            //                 <a type='button' href='$editUrl' class='inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-4 py-3 text-sm bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300'>
            //                     <span class='material-symbols-outlined text-lg' data-icon='edit'>edit</span>
            //                 </a>
            //             </div>";
            //         }

            //         $destroyHtml = "";
            //         $destroyHtml = "";
            //         if ($this->isCanDelete) {
            //             $destroyHtml = "<div class='col-auto'>
            //                 <button type='button' class='inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-4 py-3 text-sm bg-red-500 text-white shadow-theme-xs hover:bg-red-600 disabled:bg-brand-300' wire:click=\"showDeleteDialog($item->id)\">
            //                     <span class='material-symbols-outlined text-lg' data-icon='delete'>delete</span>
            //                 </button>
            //             </div>";
            //         }
            //         $html = "<div class='row p-0 m-0 d-flex justify-content-start flex-nowrap'>
            //             $editHtml $destroyHtml
            //         </div>";


            //         return $html;
            //     },
            // ],
            [
                'key' => 'no_jp',
                'name' => 'No Input JPN',
                'render' => function ($item) {
                    return $item->no_jp;
                }
            ],
            [
                'key' => 'name',
                'name' => 'Nama',
            ],
            [
                'key' => 'dob',
                'name' => 'Tgl Lahir',
            ],
            [
                'key' => 'facebook',
                'name' => 'Nama FB',
            ],
            [
                'key' => 'instagram',
                'name' => 'Nama IG',
            ],
            [
                'key' => 'phone',
                'name' => 'No Telp',
            ],
            [
                'key' => 'pic',
                'name' => 'PIC',
            ],
            [
                'key' => 'lpk',
                'name' => 'LPK',
            ],
            [
                'key' => 'note',
                'name' => 'Note',
            ],
            [
                'key' => 'claim_type',
                'name' => 'Jenis Pencairan',
                'render' => function ($item) {
                    $style = 'primary';
                    switch ($item->claim_type) {
                        case Client::CLAIM_TYPE_ADJUSTMENT:
                            $style = 'amber';
                            break;
                        case Client::CLAIM_TYPE_NORMAL:
                            $style = 'blue';
                            break;
                        case Client::CLAIM_TYPE_SPEED:
                            $style = 'success';
                            break;

                        default:
                            # code...
                            break;
                    }
                    return '<span class="bg-' . $style . '-50 dark:bg-' . $style . '-500/15 text-' . $style . '-600 dark:text-' . $style . '-500 text-theme-xs rounded-full px-2 py-0.5 font-medium">' . strtoupper($item->claim_type) . '</span>';
                }
            ],
            [
                'searchable' => false,
                'key' => 'esign_exists',
                'name' => 'Tanda Tangan',
                'render' => function ($item) {
                    $style = 'primary';
                    $text = 'belum';
                    switch ($item->esign_exists) {
                        case true:
                            $style = 'success';
                            $text = 'sudah';
                            break;
                        case false:
                            $style = 'warning';
                            $text = 'belum';
                            break;

                        default:
                            # code...
                            break;
                    }
                    return '<span class="bg-' . $style . '-50 dark:bg-' . $style . '-500/15 text-' . $style . '-700 dark:text-' . $style . '-500 text-theme-xs rounded-full px-2 py-0.5 font-medium">' . strtoupper($text) . '</span>';
                }
            ],
            [
                'searchable' => false,
                'key' => 'confirmation_exists',
                'name' => 'Form Konfirmasi',
                'render' => function ($item) {
                    $style = 'primary';
                    $text = 'belum';
                    switch ($item->confirmation_exists) {
                        case true:
                            $style = 'success';
                            $text = 'sudah';
                            break;
                        case false:
                            $style = 'warning';
                            $text = 'belum';
                            break;

                        default:
                            # code...
                            break;
                    }
                    return '<span class="bg-' . $style . '-50 dark:bg-' . $style . '-500/15 text-' . $style . '-700 dark:text-' . $style . '-500 text-theme-xs rounded-full px-2 py-0.5 font-medium">' . strtoupper($text) . '</span>';
                }
            ],
            [
                'key' => 'nenkin_80_received_type',
                'name' => 'Status Penerimaan Nenkin 80%',
                'render' => function ($item) {
                    return $item->nenkin_80_received_type ? ClientConfirmation::NENKIN_80_RECEIVED_TYPE_CHOICE[$item->nenkin_80_received_type] : '-';
                }
            ],
            [
                'key' => 'current_position',
                'name' => 'Posisi Saat Ini',
                'render' => function ($item) {
                    return $item->current_position ?? '-';
                }
            ],
            [
                'key' => 'back_to_jp_type',
                'name' => 'Rencana Kembali Ke Jepang',
                'render' => function ($item) {
                    return $item->back_to_jp_type ? ClientConfirmation::BACK_TO_JP_TYPE_CHOICE[$item->back_to_jp_type] : '-';
                }
            ],
            [
                'key' => 'departure_type',
                'name' => 'Estimasi Bulan Kembali Ke Jepang',
                'render' => function ($item) {
                    return $item->departure_type ?? '-';
                }
            ],
            [
                'key' => 'departure_month',
                'name' => 'Bulan Kembali Ke Jepang',
                'render' => function ($item) {
                    if (blank($item->departure_month)) {
                        return '-';
                    }

                    return Carbon::create()
                        ->month((int) $item->departure_month)
                        ->locale('id')
                        ->translatedFormat('F');
                }
            ],
            [
                'key' => 'departure_year',
                'name' => 'Tahun Kembali Ke Jepang',
                'render' => fn($item) => $item->departure_year ?? '-',
            ],
            [
                'key' => 'estimated_return_month',
                'name' => 'Bulan Kepulangan',
                'render' => function ($item) {
                    if (blank($item->estimated_return_month)) {
                        return '-';
                    }

                    return Carbon::create()
                        ->month((int) $item->estimated_return_month)
                        ->locale('id')
                        ->translatedFormat('F');
                }
            ],
            [
                'key' => 'estimated_return_year',
                'name' => 'Tahun Kepulangan',
                'render' => fn($item) => $item->estimated_return_year ?? '',
            ],
            [
                'key' => 'nik',
                'name' => 'NIK',
                'render' => fn($item) => $item->nik ?? '',
            ],
            [
                'key' => 'indonesia_address',
                'name' => 'Alamat Indonesia',
                'render' => fn($item) => $item->indonesia_address ?? '',
            ],
        ];
    }

    public function getQuery(): Builder
    {
        return \App\Models\Client\Client::query()
            ->select(
                'clients.*',
                'cc.nenkin_80_received_type',
                'cc.current_position',
                'cc.back_to_jp_type',
                'cc.departure_type',
                'cc.departure_month',
                'cc.departure_year',
                'cc.estimated_return_month',
                'cc.estimated_return_year',
                'cc.nik',
                'cc.indonesia_address',
            )
            ->selectRaw("
        EXISTS (
            SELECT 1
            FROM client_esigns
            WHERE client_esigns.client_id = clients.id
        ) as esign_exists,
        EXISTS (
            SELECT 1
            FROM client_confirmations
            WHERE client_confirmations.client_id = clients.id
        ) as confirmation_exists
    ")
            ->join('client_confirmations as cc', function ($j) {
                $j->on('cc.client_id', '=', 'clients.id')
                    ->whereNull('cc.deleted_at');
            })
            ->whereHas('user')
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('source_file_name', $this->selectedBatch);
            })

            ->when($this->bulkPic && $this->isAdmin, function ($q) {
                $q->where('pic', 'ILIKE', '%' . $this->bulkPic . '%');
            })
            ->when(!$this->isAdmin, function ($q) {
                $q->where('pic', 'ILIKE', '%' . $this->bulkPic . '%');
            })
            ->when($this->filter_claim_type, function ($q) {
                $q->where('clients.claim_type', $this->filter_claim_type);
            });
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
        $fileName = "Data Client Registered " . Carbon::now()->format('Y-m-d H:i:s');
        consoleLog($this, [
            $this->batch_name,
            $this->filter_claim_type,
            $this->bulkPic
        ]);
        return ExportHelper::export(
            $type,
            $fileName,
            ClientRepository::getClientRegistered($this->batch_name, $this->filter_claim_type, $this->bulkPic),
            'app.master-client-registered.export',
            [
                'title' => 'Data Client Registered',
                'type' => $type,
            ],
            [
                'size' => 'legal',
                'orientation' => 'landscape',
            ]
        );
    }

    public function getView(): string
    {
        return 'livewire.master-client-registered.datatable';
    }
}
