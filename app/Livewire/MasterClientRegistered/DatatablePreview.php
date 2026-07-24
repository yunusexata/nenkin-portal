<?php

namespace App\Livewire\MasterClientRegistered;

use App\Models\Client\Client;
use App\Models\Client\ClientClosingHistory;
use App\Models\Client\ClientConfirmation;
use App\Repositories\Client\ClientClosingHistoryRepository;
use App\Repositories\Client\ClientRepository;
use App\Traits\Livewire\WithDatatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class DatatablePreview extends Component
{
    use WithDatatable;

    public $collection;
    public $selectedBatch;
    public $closingHistories = [];
    public $bulkPic;
    public $filter_claim_type;
    public $totals = [];

    public $type;
    public $name;

    protected $listeners = [
        'getAttributeData' => 'getAttributeData',
    ];

    public function mount()
    {
        if ($this->selectedBatch) {
            $this->closingHistories = ClientClosingHistoryRepository::getBy([
                ['source_file_name', $this->selectedBatch]
            ]);
        }
    }
    public function onMount() {}

    #[On('refresh-table')]
    public function refreshTable()
    {
        $this->resetPage();
    }

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

    public function getColumns(): array
    {
        return [
            [
                'key' => 'name',
            ],
        ];
    }

    // public function getQuery(): Builder
    // {
    //     return RegencyRepository::datatable();
    // }

    public function getAttributeData($name, $type)
    {
        $this->name = $name;
        $this->type = $type;

        consoleLog($this, 'getAttributeData oi');
    }

    public function getQuery(): Builder
    {
        $query = ClientClosingHistory::query();
        if ($this->type == 'fix') {
            if ($this->name == 'registered') {
                $query = ClientRepository::getClientRegistered(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
            if ($this->name === 'unclosed') {
                $query = ClientRepository::getClientUnclosing(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
            if ($this->name === 'unconfirmed') {
                $query = ClientRepository::getClientUnregistered(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
            if ($this->name === 'kokumin') {
                $query = ClientRepository::getClientKokumin(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
        } else {
            $query = ClientRepository::getClientRegistered(
                $this->selectedBatch,
                $this->filter_claim_type,
                $this->bulkPic,
                $this->name,
                true
            );
        }
        // $query->join('client_esigns', function ($j) {
        //     $j->on('client_esigns.client_id', '=', 'clients.id')
        //         ->whereNull('client_esigns.deleted_at');
        // });

        // 1. Clone the query
        $totalsQuery = clone $query;

        // 2. FIX: Strip out conflicting clauses from the original query
        $totalsQuery->setEagerLoads([]); // Remove relationships to save memory
        $totalsQuery->reorder();         // Clears the "ORDER BY clients.is_exported"
        $totalsQuery->limit(null)->offset(null); // Clears any pagination limits

        // 3. Join the relationship tables
        $totalsQuery->leftJoin('client_esigns', function ($j) {
            $j->on('client_esigns.client_id', '=', 'clients.id')
                ->whereNull('client_esigns.deleted_at');
        });

        $this->totals = [];

        if ($this->selectedBatch) {
            // 4. Execute the conditional aggregation
            $this->totals = $totalsQuery->select([

                // AI
                DB::raw("SUM(CASE WHEN clients.pic = 'ainul' THEN 1 ELSE 0 END) as total_ai"),
                // MT
                DB::raw("SUM(CASE WHEN clients.pic = 'kim' THEN 1 ELSE 0 END) as total_mt"),
                // SN
                DB::raw("SUM(CASE WHEN clients.pic = 'slamet' THEN 1 ELSE 0 END) as total_sn"),

                // KOKUMIN
                DB::raw("SUM(CASE WHEN clients.claim_type = '" . Client::CLAIM_TYPE_KOKUMIN . "' THEN 1 ELSE 0 END) as total_kokumin"),

                // PENDING
                DB::raw("SUM(CASE WHEN client_confirmations.claim_type IN ('" . Client::CLAIM_TYPE_SPEED . "', '" . Client::CLAIM_TYPE_ADJUSTMENT . "') AND client_esigns.id IS NULL THEN 1 ELSE 0 END) as total_pending"),

                // SPEED
                DB::raw("SUM(CASE WHEN client_confirmations.claim_type = '" . Client::CLAIM_TYPE_SPEED . "' AND client_esigns.id IS NOT NULL THEN 1 ELSE 0 END) as total_speed"),

                // ADJUSTMENT
                DB::raw("SUM(CASE WHEN client_confirmations.claim_type = '" . Client::CLAIM_TYPE_ADJUSTMENT . "' AND client_esigns.id IS NOT NULL THEN 1 ELSE 0 END) as total_adjustment"),

                // RE
                DB::raw("SUM(CASE WHEN client_confirmations.claim_type = '" . Client::CLAIM_TYPE_NORMAL . "' AND client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_JEPANG . "' THEN 1 ELSE 0 END) as total_re"),

                // JPN
                DB::raw("SUM(CASE WHEN clients.claim_type <> '" . Client::CLAIM_TYPE_KOKUMIN . "' AND client_confirmations.id IS NULL OR (client_confirmations.claim_type = '" . Client::CLAIM_TYPE_NORMAL . "' AND client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_INDONESIA . "' AND client_confirmations.back_to_jp_type = '" . ClientConfirmation::BACK_TO_JP_TYPE_YES . "') THEN 1 ELSE 0 END) as total_jpn"),
            ])->first();
        }


        return $query;
    }

    public function getView(): string
    {
        return 'livewire.master-client-registered.datatable-preview';
    }
}
