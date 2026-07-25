<?php

namespace App\Livewire\MasterClientRegistered;

use App\Helpers\Alert;
use App\Models\Client\Client;
use App\Models\Client\ClientClosingHistory;
use App\Models\Client\ClientConfirmation;
use App\Repositories\Client\ClientClosingHistoryRepository;
use App\Repositories\Client\ClientRepository;
use App\Traits\Livewire\WithDatatable;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;

class PreviewModal extends Component
{
    use WithDatatable;
    public $collection;
    #[Url(except: '')]
    public $selectedBatch;
    public $closingHistories = [];
    public $bulkPic;
    public $filter_claim_type;
    public $totals = [];

    public $type;
    public $name;
    public function mount()
    {
        if ($this->selectedBatch) {
            $this->closingHistories = ClientClosingHistoryRepository::getBy([
                ['source_file_name', $this->selectedBatch]
            ]);
        }
    }

    #[On('refresh-table')]
    public function refreshTable()
    {
        $this->resetPage();
    }

    public function closeModal() {}

    #[On('modal-filter')]
    public function modalFilter($filter)
    {
        foreach ($filter as $key => $value) {
            $this->$key = $value;
            if ($key === 'selectedBatch') {
                $this->closingHistories = ClientClosingHistoryRepository::getBy([
                    ['source_file_name', $this->selectedBatch]
                ]);
            }
        }
    }

    #[On('validate-modal')]
    public function validateModal()
    {
        if (blank($this->selectedBatch) || $this->selectedBatch === 'all') {
            Alert::fail($this, "Gagal", 'Silakan pilih batch terlebih dahulu');
            return;
        }
        consoleLog($this, 'trytyrty');
        $this->name = 'registered';
        $this->type = 'fix';
    }

    public function getData($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getColumns(): array
    {
        return [
            [
                'key' => 'name',
            ],
        ];
    }

    // public function render()
    // {
    //     return view('livewire.master-client-registered.preview-modal');
    // }
    public function baseQuery($batchName = null, $claimType = null, $pic = null, $closing_id = null, $orderByClaimType = false): Builder
    {
        if ($this->type == 'fix') {
            if ($this->name == 'registered') {

                // REGISTERED
                $query = Client::query()
                    ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
                    ->join('client_confirmations', function ($j) {
                        $j->on('client_confirmations.client_id', '=', 'clients.id')
                            ->whereNull('client_confirmations.deleted_at');
                    })
                    ->when($closing_id, function ($q) use ($closing_id) {
                        $q->where('client_confirmations.client_closing_history_id', $closing_id);
                    })
                    ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                        $q->where('clients.source_file_name', $batchName);
                    })
                    ->when($claimType && $claimType === Client::CLAIM_TYPE_NORMAL, function ($q) {
                        $q->where('client_confirmations.claim_type', Client::CLAIM_TYPE_NORMAL);
                    })
                    ->when($claimType &&
                        in_array(
                            $claimType,
                            [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT]
                        ), function ($q) use ($claimType) {
                        $q->where('client_confirmations.claim_type', $claimType)
                            ->whereHas('esign');
                    })
                    ->when($claimType && $claimType === Client::STATUS_PENDING, function ($q) {
                        $q->whereIn('client_confirmations.claim_type', [Client::CLAIM_TYPE_ADJUSTMENT, Client::CLAIM_TYPE_SPEED])
                            ->whereDoesntHave('esign');
                    })
                    ->when($pic, function ($q) use ($pic) {
                        $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
                    })
                    ->whereHas('confirmation');
            }
            if ($this->name === 'unclosed') {

                // UNCLOSING
                $query = Client::query()
                    ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
                    ->join('client_confirmations', function ($j) {
                        $j->on('client_confirmations.client_id', '=', 'clients.id')
                            ->whereNull('client_confirmations.deleted_at');
                    })
                    ->where('client_confirmations.client_closing_history_id', null)
                    ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                        $q->where('clients.source_file_name', $batchName);
                    })
                    ->when($claimType && $claimType === Client::CLAIM_TYPE_NORMAL, function ($q) {
                        $q->where('client_confirmations.claim_type', Client::CLAIM_TYPE_NORMAL);
                    })
                    ->when($claimType &&
                        in_array(
                            $claimType,
                            [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT]
                        ), function ($q) use ($claimType) {
                        $q->where('client_confirmations.claim_type', $claimType)
                            ->whereHas('esign');
                    })
                    ->when($claimType && $claimType === Client::STATUS_PENDING, function ($q) {
                        $q->whereIn('client_confirmations.claim_type', [Client::CLAIM_TYPE_ADJUSTMENT, Client::CLAIM_TYPE_SPEED])
                            ->whereDoesntHave('esign');
                    })
                    ->when($pic, function ($q) use ($pic) {
                        $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
                    });
            }
            if ($this->name === 'unconfirmed') {

                // UNREGISTERED

                $query = Client::query()
                    ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
                    ->leftJoin('client_confirmations', function ($j) {
                        $j->on('client_confirmations.client_id', '=', 'clients.id')
                            ->whereNull('client_confirmations.deleted_at');
                    })
                    ->when($closing_id, function ($q) use ($closing_id) {
                        $q->where('client_confirmations.client_closing_history_id', $closing_id);
                    })
                    ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                        $q->where('clients.source_file_name', $batchName);
                    })
                    ->when($claimType && $claimType === Client::CLAIM_TYPE_NORMAL, function ($q) {
                        $q->where('client_confirmations.claim_type', Client::CLAIM_TYPE_NORMAL);
                    })
                    ->when($claimType &&
                        in_array(
                            $claimType,
                            [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT]
                        ), function ($q) use ($claimType) {
                        $q->where('client_confirmations.claim_type', $claimType)
                            ->whereHas('esign');
                    })
                    ->when($claimType && $claimType === Client::STATUS_PENDING, function ($q) {
                        $q->whereIn('client_confirmations.claim_type', [Client::CLAIM_TYPE_ADJUSTMENT, Client::CLAIM_TYPE_SPEED])
                            ->whereDoesntHave('esign');
                    })
                    ->when($pic, function ($q) use ($pic) {
                        $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
                    })
                    ->whereDoesntHave('confirmation');
            }
            if ($this->name === 'kokumin') {

                // KOKUMIN

                $query = Client::query()
                    ->where('clients.claim_type', '=', Client::CLAIM_TYPE_KOKUMIN)
                    ->leftJoin('client_confirmations', function ($j) {
                        $j->on('client_confirmations.client_id', '=', 'clients.id')
                            ->whereNull('client_confirmations.deleted_at');
                    })
                    ->when($closing_id, function ($q) use ($closing_id) {
                        $q->where('client_confirmations.client_closing_history_id', $closing_id);
                    })
                    ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                        $q->where('clients.source_file_name', $batchName);
                    })
                    ->when($claimType && $claimType === Client::CLAIM_TYPE_NORMAL, function ($q) {
                        $q->where('client_confirmations.claim_type', Client::CLAIM_TYPE_NORMAL);
                    })
                    ->when($claimType &&
                        in_array(
                            $claimType,
                            [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT]
                        ), function ($q) use ($claimType) {
                        $q->where('client_confirmations.claim_type', $claimType)
                            ->whereHas('esign');
                    })
                    ->when($claimType && $claimType === Client::STATUS_PENDING, function ($q) {
                        $q->whereIn('client_confirmations.claim_type', [Client::CLAIM_TYPE_ADJUSTMENT, Client::CLAIM_TYPE_SPEED])
                            ->whereDoesntHave('esign');
                    })
                    ->when($pic, function ($q) use ($pic) {
                        $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
                    })
                    ->whereDoesntHave('confirmation');
            }
        } else {
            // REGISTERED
            $query = Client::query()
                ->where('clients.claim_type', '!=', Client::CLAIM_TYPE_KOKUMIN)
                ->join('client_confirmations', function ($j) {
                    $j->on('client_confirmations.client_id', '=', 'clients.id')
                        ->whereNull('client_confirmations.deleted_at');
                })
                ->when($closing_id, function ($q) use ($closing_id) {
                    $q->where('client_confirmations.client_closing_history_id', $closing_id);
                })
                ->when($batchName && $batchName != 'all', function ($q) use ($batchName) {
                    $q->where('clients.source_file_name', $batchName);
                })
                ->when($claimType && $claimType === Client::CLAIM_TYPE_NORMAL, function ($q) {
                    $q->where('client_confirmations.claim_type', Client::CLAIM_TYPE_NORMAL);
                })
                ->when($claimType &&
                    in_array(
                        $claimType,
                        [Client::CLAIM_TYPE_SPEED, Client::CLAIM_TYPE_ADJUSTMENT]
                    ), function ($q) use ($claimType) {
                    $q->where('client_confirmations.claim_type', $claimType)
                        ->whereHas('esign');
                })
                ->when($claimType && $claimType === Client::STATUS_PENDING, function ($q) {
                    $q->whereIn('client_confirmations.claim_type', [Client::CLAIM_TYPE_ADJUSTMENT, Client::CLAIM_TYPE_SPEED])
                        ->whereDoesntHave('esign');
                })
                ->when($pic, function ($q) use ($pic) {
                    $q->where('clients.pic', 'ILIKE', '%' . $pic . '%');
                })
                ->whereHas('confirmation');
        }
        return $query;
    }
    public function getQuery(): Builder
    {
        $totalsQuery = ClientClosingHistory::query();
        $query = $totalsQuery;
        if ($this->type == 'fix') {
            if ($this->name == 'registered') {
                $totalsQuery = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
                $query = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )
                    ->select('clients.*')
                    ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
                    AND client_esigns.deleted_at IS NULL
            ) as esign_exists
        ")
                    ->orderByDesc('clients.is_exported')
                    ->orderBy('clients.exported_at', 'ASC')
                    ->orderByDesc('clients.no_jp');
            }
            if ($this->name === 'unclosed') {
                $totalsQuery = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
                $query = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )
                    ->select('clients.*')
                    ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
                    AND client_esigns.deleted_at IS NULL
            ) as esign_exists
        ")
                    ->orderByDesc('clients.is_exported')
                    ->orderBy('clients.exported_at', 'ASC')
                    ->orderByDesc('clients.no_jp');
            }
            if ($this->name === 'unconfirmed') {
                $totalsQuery = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
                $query = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )
                    ->select('clients.*')
                    ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
                    AND client_esigns.deleted_at IS NULL
            ) as esign_exists
        ")
                    ->orderByDesc('clients.is_exported')
                    ->orderByDesc('clients.no_jp');
            }
            if ($this->name === 'kokumin') {
                $totalsQuery = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
                $query = $this->baseQuery(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )
                    ->select('clients.*')
                    ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
                    AND client_esigns.deleted_at IS NULL
            ) as esign_exists
        ")
                    ->orderByDesc('clients.is_exported')
                    ->orderByDesc('clients.no_jp');
            }
        } else {
            $totalsQuery = $this->baseQuery(
                $this->selectedBatch,
                $this->filter_claim_type,
                $this->bulkPic,
                $this->name,
                true
            );
            $query = $this->baseQuery(
                $this->selectedBatch,
                $this->filter_claim_type,
                $this->bulkPic,
                $this->name,
                true
            )
                ->select('clients.*')
                ->selectRaw("
            EXISTS (
                SELECT 1
                FROM client_esigns
                WHERE client_esigns.client_id = clients.id
                    AND client_esigns.deleted_at IS NULL
            ) as esign_exists
        ")
                ->orderByDesc('clients.is_exported')
                ->orderByDesc('client_confirmations.claim_type', 'DESC')
                ->orderBy('clients.exported_at', 'ASC')
                ->orderByDesc('clients.no_jp');
        }
        // $query->join('client_esigns', function ($j) {
        //     $j->on('client_esigns.client_id', '=', 'clients.id')
        //         ->whereNull('client_esigns.deleted_at');
        // });

        // 1. Clone the query

        // 2. FIX: Strip out conflicting clauses from the original query
        // $totalsQuery->setEagerLoads([]); // Remove relationships to save memory
        // $totalsQuery->reorder();         // Clears the "ORDER BY clients.is_exported"
        // $totalsQuery->limit(null)->offset(null); // Clears any pagination limits

        // 3. Join the relationship tables
        // $totalsQuery->leftJoin('client_esigns', function ($j) {
        //     $j->on('client_esigns.client_id', '=', 'clients.id')
        //         ->whereNull('client_esigns.deleted_at');
        // });

        // dd($totalsQuery->count());

        // 4. Execute the conditional aggregation
        $this->totals = [];

        if ($this->selectedBatch) {
            $this->totals = $totalsQuery
                ->selectRaw("
        SUM(
            CASE
                WHEN clients.pic = 'ainul'
                THEN 1
                ELSE 0
            END
        ) as total_ai
    ")
                ->first();
            // $this->totals = $totalsQuery->select([

            //     // AI
            //     DB::raw("SUM(CASE WHEN clients.pic = 'ainul' THEN 1 ELSE 0 END) as total_ai"),
            //     // MT
            //     DB::raw("SUM(CASE WHEN clients.pic = 'kim' THEN 1 ELSE 0 END) as total_mt"),
            //     // SN
            //     DB::raw("SUM(CASE WHEN clients.pic = 'slamet' THEN 1 ELSE 0 END) as total_sn"),

            //     // KOKUMIN
            //     DB::raw("SUM(CASE WHEN clients.claim_type = '" . Client::CLAIM_TYPE_KOKUMIN . "' THEN 1 ELSE 0 END) as total_kokumin"),

            //     // PENDING
            //     DB::raw("SUM(CASE WHEN client_confirmations.claim_type IN ('" . Client::CLAIM_TYPE_SPEED . "', '" . Client::CLAIM_TYPE_ADJUSTMENT . "') AND  NOT EXISTS (
            //     SELECT 1
            //     FROM client_esigns ce
            //     WHERE ce.client_id = clients.id
            //     AND ce.deleted_at IS NULL
            // )
            //  THEN 1 ELSE 0 END) as total_pending"),

            //     // SPEED
            //     DB::raw("SUM(CASE WHEN client_confirmations.claim_type = '" . Client::CLAIM_TYPE_SPEED . "' 
            // AND EXISTS (
            //     SELECT 1
            //     FROM client_esigns ce
            //     WHERE ce.client_id = clients.id
            //     AND ce.deleted_at IS NULL
            // )
            //  THEN 1 ELSE 0 END) as total_speed"),

            //     // ADJUSTMENT
            //     DB::raw("SUM(CASE WHEN client_confirmations.claim_type = '" . Client::CLAIM_TYPE_ADJUSTMENT . "' 
            // AND EXISTS (
            //     SELECT 1
            //     FROM client_esigns ce
            //     WHERE ce.client_id = clients.id
            //     AND ce.deleted_at IS NULL
            // )
            //  THEN 1 ELSE 0 END) as total_adjustment"),

            //     // RE
            //     DB::raw("SUM(CASE WHEN client_confirmations.claim_type = '" . Client::CLAIM_TYPE_NORMAL . "' AND client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_JEPANG . "' THEN 1 ELSE 0 END) as total_re"),

            //     // JPN
            //     DB::raw("SUM(CASE WHEN clients.claim_type <> '" . Client::CLAIM_TYPE_KOKUMIN . "' AND client_confirmations.id IS NULL OR (client_confirmations.claim_type = '" . Client::CLAIM_TYPE_NORMAL . "' AND client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_INDONESIA . "' AND client_confirmations.back_to_jp_type = '" . ClientConfirmation::BACK_TO_JP_TYPE_YES . "') THEN 1 ELSE 0 END) as total_jpn"),
            // ])->first();
        }
        return $query ?? ClientClosingHistory::query();
    }

    public function getView(): string
    {
        // return 'livewire.master-client-registered.datatable-preview';
        return 'livewire.master-client-registered.preview-modal';
    }
}
