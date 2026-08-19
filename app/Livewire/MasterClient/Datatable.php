<?php

namespace App\Livewire\MasterClient;

use App\Helpers\Alert;
use App\Helpers\PermissionHelper;
use App\Models\Client\Client;
use App\Models\GensenForm\GensenFormLink;
use App\Repositories\Account\UserRepository;
use App\Repositories\Client\ClientRepository;
use App\Repositories\Exata\ExataFormCandidateRepository;
use App\Repositories\GensenForm\GensenFormLinkRepository;
use App\Traits\Livewire\WithDatatable;
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

    public $isAdmin = false;
    public $status;

    #[Url(except: '')]
    public $selectedBatch;
    public $filter_claim_type;

    #[Url(except: '')]
    public $filter_data_type;
    public $batches = [];

    #[Url(except: '')]
    public $bulkPic;

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

    public function updatedbulkPic($value)
    {

        $this->dispatch('modal-filter', [
            'bulkPic' => $value,
        ]);
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
        $this->isAdmin = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_FILTER_ALL_PIC, PermissionHelper::TYPE_READ));
        if (!$this->selectedBatch) {
            $this->selectedBatch = 'all';
        }
        if (!$this->bulkPic) {
            $this->bulkPic = $authUser->hasPermissionTo(PermissionHelper::transform(PermissionHelper::ACCESS_FILTER_ALL_PIC, PermissionHelper::TYPE_READ)) ? null : auth()->user()->pic;
        }
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
        $column = [

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
                'name' => 'Action',
                'sortable' => false,
                'searchable' => false,
                'render' => function ($item) {
                    $link = route('client.create', $item->magicLink->token ?? '-');

                    $linkHtml = "
                        <div class='w-auto'>
                            <button class='p-1 inline-flex items-center justify-center text-emerald-600 hover:bg-emerald-50 rounded transition-colors'
                                onclick=\"copyToClipboard('$link')\"
                            >
                                <span class='material-symbols-outlined text-lg' data-icon='edit'>link</span>
                            </button>
                        </div>
                    ";

                    $html = "<div class='flex flex-nowrap justify-start p-0 m-0'>
                        $linkHtml
                    </div>";

                    return $html;
                },
            ],
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
                'searchable' => false,
                'key' => 'confirmation_exists',
                'name' => 'Konfimasi',
                'render' => function ($item) {
                    $style = 'success';
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
                'key' => 'esign_exists',
                'name' => 'TTD',
                'render' => function ($item) {
                    $style = 'success';
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

        ];

        return $column;
    }


    public function updatedFilterClaimType($value)
    {
        $this->dispatch('modal-filter', [
            'filter_claim_type' => $value,
        ]);
    }
    public function updatedFilterDataType($value)
    {
        $this->dispatch('modal-filter', [
            'filter_data_type' => $value,
        ]);
    }

    public function getQuery(): Builder
    {
        return \App\Models\Client\Client::query()
            ->select('clients.*')
            ->selectRaw("
                EXISTS (
                    SELECT 1
                    FROM client_confirmations
                    WHERE client_confirmations.client_id = clients.id
                        AND client_confirmations.deleted_at IS NULL
                ) as confirmation_exists,
                EXISTS (
                    SELECT 1
                    FROM client_esigns
                    WHERE client_esigns.client_id = clients.id
                        AND client_esigns.deleted_at IS NULL
                ) as esign_exists,
                EXISTS (
                    SELECT 1
                    FROM users
                    WHERE users.client_id = clients.id
                        AND users.deleted_at IS NULL
                ) as user_exists
            ")
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('clients.source_file_name', $this->selectedBatch);
            })
            ->when($this->bulkPic && $this->isAdmin, function ($q) {
                $q->where('clients.pic', 'ILIKE', '%' . $this->bulkPic . '%');
            })
            ->when(!$this->isAdmin, function ($q) {
                $q->where('clients.pic', 'ILIKE', '%' . $this->bulkPic . '%');
            })
            ->whereDoesntHave('esign')
            ->when($this->filter_data_type && $this->filter_data_type === Client::FILTER_DATA_TYPE_CONFIRMATION, function ($q) {
                $q->whereHas('confirmation');
            })
            ->when($this->filter_data_type && $this->filter_data_type === Client::FILTER_DATA_TYPE_NOT_CONFIRMATION, function ($q) {
                $q->whereDoesntHave('confirmation')
                    ->whereHas('user');
            })
            ->when($this->filter_data_type && $this->filter_data_type === Client::FILTER_DATA_TYPE_REGISTERED, function ($q) {
                $q->whereHas('user');
            })
            ->when($this->filter_data_type && $this->filter_data_type === Client::FILTER_DATA_TYPE_NOT_REGISTERED, function ($q) {
                $q->whereDoesntHave('user');
            })
            ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
            ->whereDoesntHave('confirmation');
    }

    public function getView(): string
    {
        return 'livewire.master-client.datatable';
    }
}
