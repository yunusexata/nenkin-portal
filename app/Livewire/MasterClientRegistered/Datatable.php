<?php

namespace App\Livewire\MasterClientRegistered;

use App\Helpers\Alert;
use App\Helpers\ExportHelper;
use App\Helpers\PermissionHelper;
use App\Models\Client\Client;
use App\Models\Client\ClientConfirmation;
use App\Models\GensenForm\GensenFormLink;
use App\Repositories\Account\UserRepository;
use App\Repositories\Client\ClientConfirmationRepository;
use App\Repositories\Client\ClientEsignRepository;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Exata\ExataFormCandidateRepository;
use App\Repositories\GensenForm\GensenFormLinkRepository;
use App\Traits\Livewire\WithDatatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;


class Datatable extends Component
{
    use WithDatatable;

    public $isCanUpdate;
    public $isCanDelete;
    public $isCanUpdateBookingTime;
    public $isCanUpdateDetail;

    public $isCanGetAll = false;
    public $status;

    #[Url(except: '')]
    public $selectedBatch;
    public $batches = [];

    #[Url(except: '')]
    public $bulkPic;

    public $batch_name;

    #[Url(except: '')]
    public $filter_claim_type;
    public $filter_export_status;

    // Delete Dialog
    public $targetDeleteId;

    public function selectBatch($batchName)
    {
        $this->selectedBatch = $batchName;
        $this->resetPage();
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
        $this->loadBatches();

        $authUser = UserRepository::authenticatedUser();
        $this->isCanUpdate = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_MASTER_CLIENT_REGISTERED, PermissionHelper::TYPE_UPDATE));
        $this->isCanDelete = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_MASTER_CLIENT_REGISTERED, PermissionHelper::TYPE_DELETE));
        $this->isCanGetAll = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_MASTER_CLIENT_REGISTERED, PermissionHelper::TYPE_UPDATE));
        if (!$this->selectedBatch) {
            $this->selectedBatch = 'all';
        }
        if (!$this->bulkPic) {
            $this->bulkPic = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_MASTER_CLIENT_REGISTERED, PermissionHelper::TYPE_UPDATE)) ? null : auth()->user()->pic;
        }
    }

    #[On('on-delete-confirmation-dialog-confirm')]
    public function onDialogDeleteConfirmationConfirm()
    {
        if (!$this->isCanDelete || $this->targetDeleteId == null) {
            return;
        }

        ClientConfirmationRepository::deleteBy([
            ['client_id', $this->targetDeleteId]
        ]);
        ClientEsignRepository::deleteBy([
            ['client_id', $this->targetDeleteId]
        ]);
        Alert::success($this, 'Berhasil', 'Data berhasil dihapus');
    }

    #[On('on-delete-confirmation-dialog-cancel')]
    public function onDialogDeleteConfirmationCancel()
    {
        $this->targetDeleteId = null;
    }

    public function showDeleteConfirmationDialog($id)
    {
        $this->targetDeleteId = $id;

        Alert::confirmation(
            $this,
            Alert::ICON_QUESTION,
            "Hapus Data",
            "Apakah Anda Yakin Ingin Menghapus Data Ini ?",
            "on-delete-confirmation-dialog-confirm",
            "on-delete-confirmation-dialog-cancel",
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
                'searchable' => false,
                'sortable' => false,
                'name' => 'No',
                'render' => function ($item, $index) {
                    $page = $this->getPage();

                    return (($page - 1) * $this->length) + $index + 1;
                }
            ],
            [
                'key' => 'clients.no_jp',
                'name' => 'No Input JPN',
                'render' => function ($item) {
                    return $item->no_jp;
                }
            ],
            [
                'key' => 'clients.name',
                'name' => 'Nama',
                'render' => fn($item) => $item->name,
            ],
            [
                'key' => 'clients.dob',
                'name' => 'Tgl Lahir',
                'render' => fn($item) => $item->dob,
            ],
            [
                'key' => 'clients.facebook',
                'name' => 'Nama FB',
                'render' => fn($item) => $item->facebook,
            ],
            [
                'key' => 'clients.instagram',
                'name' => 'Nama IG',
                'render' => fn($item) => $item->instagram,
            ],
            [
                'key' => 'clients.phone',
                'name' => 'No Telp',
                'render' => fn($item) => $item->phone,
            ],
            [
                'key' => 'clients.pic',
                'name' => 'PIC',
                'render' => fn($item) => $item->pic,
            ],
            [
                'key' => 'clients.lpk',
                'name' => 'LPK',
                'render' => fn($item) => $item->lpk,
            ],
            [
                'key' => 'clients.note',
                'name' => 'Note',
                'render' => fn($item) => $item->note,
            ],
            [
                'searchable' => false,
                'key' => 'user_exists',
                'name' => 'Buat Akun',
                'render' => function ($item) {
                    $style = 'success';
                    $text = 'belum';
                    switch ($item->user_exists) {
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
                'sortable' => false,
                'name' => 'Jenis Pencairan',
                'render' => function ($item) {
                    $style = 'primary';
                    $text = '';
                    if (
                        in_array(
                            $item?->confirmation?->claim_type,
                            [
                                Client::CLAIM_TYPE_ADJUSTMENT,
                                Client::CLAIM_TYPE_SPEED,
                            ]
                        ) && !$item?->esign
                    ) {
                        $text = Client::CLAIM_TYPE_PENDING;
                        $style = 'red';
                    } elseif (
                        $item?->confirmation?->claim_type == Client::CLAIM_TYPE_ADJUSTMENT
                        && $item?->esign
                    ) {
                        $text = Client::CLAIM_TYPE_ADJUSTMENT;
                        $style = 'amber';
                    } elseif (
                        $item?->confirmation?->claim_type == Client::CLAIM_TYPE_SPEED
                        && $item?->esign
                    ) {
                        $text = Client::CLAIM_TYPE_SPEED;
                        $style = 'success';
                    } elseif (
                        $item?->confirmation?->claim_type == Client::CLAIM_TYPE_NORMAL
                    ) {
                        $text = Client::CLAIM_TYPE_NORMAL;
                        $style = 'blue';
                    } elseif (!$item->confirmation) {
                        $text = Client::CLAIM_TYPE_NORMAL;
                        $style = 'blue';
                    }
                    return '<span class="bg-' . $style . '-50 dark:bg-' . $style . '-500/15 text-' . $style . '-600 dark:text-' . $style . '-500 text-theme-xs rounded-full px-2 py-0.5 font-medium">' . strtoupper($text) . '</span>';
                }
            ],
            [
                'key'  => 'is_exported',
                'name' => 'Status Ekspor',
                'sortable' => false,
                'searchable' => false,
                'render' => function ($item) {
                    if ($item->is_exported) {
                        return '<span class="bg-success-50 dark:bg-success-500/15 text-success-600 dark:text-success-500 text-theme-xs rounded-full px-2 py-0.5 font-medium">EXPORTED</span>';
                    }
                    return '<span class="bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-theme-xs rounded-full px-2 py-0.5 font-medium">UN-EXPORTED</span>';
                }
            ],
            [
                'searchable' => false,
                'key' => 'nenkin_80_received_type',
                'name' => 'Status Penerimaan Nenkin 80%',
                'render' => function ($item) {
                    return $item->nenkin_80_received_type ? ClientConfirmation::NENKIN_80_RECEIVED_TYPE_CHOICE[$item->nenkin_80_received_type] : '-';
                }
            ],
            [
                'searchable' => false,
                'key' => 'current_position',
                'name' => 'Posisi Saat Ini',
                'render' => function ($item) {
                    return $item->current_position ?? '-';
                }
            ],
            [
                'searchable' => false,
                'key' => 'back_to_jp_type',
                'name' => 'Rencana Kembali Ke Jepang',
                'render' => function ($item) {
                    return $item->back_to_jp_type ? ClientConfirmation::BACK_TO_JP_TYPE_CHOICE[$item->back_to_jp_type] : '-';
                }
            ],
            [
                'searchable' => false,
                'key' => 'departure_type',
                'name' => 'Estimasi Bulan Kembali Ke Jepang',
                'render' => function ($item) {
                    return $item->departure_type ?? '-';
                }
            ],
            [
                'searchable' => false,
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
                'searchable' => false,
                'key' => 'departure_year',
                'name' => 'Tahun Kembali Ke Jepang',
                'render' => fn($item) => $item->departure_year ?? '-',
            ],
            [
                'searchable' => false,
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
                'searchable' => false,
                'key' => 'estimated_return_year',
                'name' => 'Tahun Kepulangan',
                'render' => fn($item) => $item->estimated_return_year ?? '',
            ],
            [
                'searchable' => false,
                'key' => 'nik',
                'name' => 'NIK',
                'render' => fn($item) => $item->nik ?? '',
            ],
            [
                'searchable' => false,
                'key' => 'indonesia_address',
                'name' => 'Alamat Indonesia',
                'render' => fn($item) => $item->indonesia_address ?? '',
            ],
            [
                'name' => 'Action',
                'sortable' => false,
                'searchable' => false,
                'render' => function ($item) {
                    $destroyHtml = "";
                    if ($this->isCanDelete) {
                        $destroyHtml = "<div class='col-auto'>
                            <button type='button' class='inline-flex items-center justify-center font-medium gap-2 rounded-lg transition px-2 py-1 text-sm bg-red-500 text-white shadow-theme-xs hover:bg-red-600 disabled:bg-brand-300' wire:click=\"showDeleteConfirmationDialog($item->id)\">
                                <span class='material-symbols-outlined text-lg' data-icon='delete'>delete</span>
                            </button>
                        </div>";
                    }
                    $html = "<div class='flex flex-nowrap justify-start p-0 m-0 gap-1'>
                        $destroyHtml
                    </div>";

                    return $html;
                },
            ],
        ];
    }

    public function getQuery(): Builder
    {
        return \App\Models\Client\Client::query()
            ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
            ->select(
                'clients.*',
                'client_confirmations.nenkin_80_received_type',
                'client_confirmations.current_position',
                'client_confirmations.back_to_jp_type',
                'client_confirmations.departure_type',
                'client_confirmations.departure_month',
                'client_confirmations.departure_year',
                'client_confirmations.estimated_return_month',
                'client_confirmations.estimated_return_year',
                'client_confirmations.nik',
                'client_confirmations.indonesia_address',
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
            ) as confirmation_exists,
            EXISTS (
                SELECT 1
                FROM users
                WHERE users.client_id = clients.id
                    AND users.deleted_at IS NULL
            ) as user_exists
        ")
            ->join('client_confirmations', function ($j) {
                $j->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->whereHas('user')
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('clients.source_file_name', $this->selectedBatch);
            })

            ->when($this->bulkPic && $this->isCanGetAll, function ($q) {
                $q->where('clients.pic', 'ILIKE', '%' . $this->bulkPic . '%');
            })
            ->when(!$this->isCanGetAll, function ($q) {
                $q->where('clients.pic', 'ILIKE', '%' . $this->bulkPic . '%');
            })
            ->when($this->filter_export_status === 'exported', function ($q) {
                $q->where('clients.is_exported', true);
            })
            ->when($this->filter_export_status === 'unexported', function ($q) {
                $q->where('clients.is_exported', false);
            })
            ->when($this->filter_claim_type && $this->filter_claim_type === Client::CLAIM_TYPE_NORMAL, function ($q) {
                $q->where('client_confirmations.claim_type', Client::CLAIM_TYPE_NORMAL);
            })
            ->when($this->filter_claim_type &&
                in_array(
                    $this->filter_claim_type,
                    [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT]
                ), function ($q) {
                $q->where('client_confirmations.claim_type', $this->filter_claim_type)
                    ->whereHas('esign');
            })
            ->when($this->filter_claim_type && $this->filter_claim_type === Client::STATUS_PENDING, function ($q) {
                $q->whereIn('client_confirmations.claim_type', [Client::CLAIM_TYPE_ADJUSTMENT, Client::CLAIM_TYPE_SPEED])
                    ->whereDoesntHave('esign');
            });
    }


    #[On('modal-filter')]
    public function modalFilter($filter)
    {
        foreach ($filter as $key => $value) {
            $this->$key = $value;
        }
    }

    #[On('showLockConfirmation')]
    public function showLockConfirmation()
    {
        if (!auth()->user()->hasRole(\App\Models\User::ROLE_HS3) && !auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)) {
            return;
        }
        if (blank($this->selectedBatch) || $this->selectedBatch === 'all') {

            Alert::fail($this, "Gagal", 'Silakan pilih batch terlebih dahulu');
            return;
        }

        Alert::confirmation(
            $this,
            Alert::ICON_QUESTION,
            "Hapus Data",
            "Apakah Anda Yakin Ingin Mengunci Batch Ini ?",
            "on-lock-confirmation-dialog-confirm",
            "on-lock-confirmation-dialog-cancel",
            "Kunci",
            "Batal",
        );
    }

    #[On('on-lock-confirmation-dialog-confirm')]
    public function onDialogLockConfirmationConfirm()
    {
        if (!auth()->user()->hasRole(\App\Models\User::ROLE_HS3) && !auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)) {
            return;
        }
        if (blank($this->selectedBatch) || $this->selectedBatch === 'all') {

            Alert::fail($this, "Gagal", 'Silakan pilih batch terlebih dahulu');
            return;
        }
        $clientsWithoutConfirmation = Client::query()
            ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
            ->where('clients.source_file_name', $this->selectedBatch)
            ->whereDoesntHave('confirmation')
            ->get();
        foreach ($clientsWithoutConfirmation as $client) {

            $client_confirmation = ClientConfirmation::firstOrCreate([
                'client_id' => $client->id,
            ], [
                'client_id' => $client->id,
                'nenkin_80_received_type' => ClientConfirmation::NENKIN_80_RECEIVED_TYPE_RECEIVED,
                'current_position' => ClientConfirmation::CURRENT_POSITION_JEPANG,
                'back_to_jp_type' => null,
                'departure_month' => null,
                'departure_year' => null,
                'estimated_return_month' => now()->addMonth()->format('m'),
                'estimated_return_year' => now()->addYear()->format('Y'),
                'indonesia_address' => null,
                'nik' => null,
            ]);
        }
        ClientConfirmation::query()
            ->whereIn('client_confirmations.claim_type', [
                Client::CLAIM_TYPE_SPEED,
                Client::CLAIM_TYPE_ADJUSTMENT,
            ])
            ->whereHas('client', function ($q) {
                $q->whereDoesntHave('esign')
                    ->where('source_file_name', $this->selectedBatch);
            })
            ->each(function ($confirmation) {
                $confirmation->update([
                    'claim_type' => Client::CLAIM_TYPE_NORMAL,
                ]);
            });

        Alert::information($this, 'Data Berhasil Disimpan');
    }

    #[On('on-lock-confirmation-dialog-cancel')]
    public function onDialogLockConfirmationCancel() {}

    #[On('export')]
    public function export($type)
    {
        $fileName = "Data Client Registered " . Carbon::now()->format('Y-m-d H:i:s');
        $query = ClientRepository::getClientRegistered(
            $this->selectedBatch,
            $this->filter_claim_type,
            $this->bulkPic
        );

        $export = ExportHelper::export(
            $type,
            $fileName,
            $query,
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

        if (auth()->user()->hasRole(\App\Models\User::ROLE_HS3)) {
            $query->update([
                'is_exported' => true,
                'exported_at' => now(),
            ]);
        }

        return $export;
    }

    public function getView(): string
    {
        return 'livewire.master-client-registered.datatable';
    }
}
