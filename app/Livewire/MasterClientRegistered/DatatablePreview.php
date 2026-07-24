<?php

namespace App\Livewire\MasterClientRegistered;

use App\Models\Client\Client;
use App\Models\Client\ClientClosingHistory;
use App\Models\Client\ClientConfirmation;
use App\Repositories\Client\ClientClosingHistoryRepository;
use App\Repositories\Client\ClientRepository;
use App\Traits\Livewire\WithDatatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
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
                'name' => 'Action',
                'sortable' => false,
                'searchable' => false,
                'render' => function ($item) {
                    $editHtml = "";

                    $id = Crypt::encrypt($item->id);
                    if ($this->isCanUpdate) {
                        $editUrl = route('regency.edit', $id);
                        $editHtml = "<div class='col-auto mb-2'>
                            <a class='btn btn-primary btn-sm' href='$editUrl'>
                                <i class='ki-duotone ki-notepad-edit fs-1'>
                                    <span class='path1'></span>
                                    <span class='path2'></span>
                                </i>
                                Ubah
                            </a>
                        </div>";
                    }

                    $destroyHtml = "";
                    if ($this->isCanDelete) {
                        $destroyHtml = "<div class='col-auto mb-2'>
                            <button class='btn btn-danger btn-sm m-0' 
                                wire:click=\"showDeleteDialog($item->id)\">
                                <i class='ki-duotone ki-trash fs-1'>
                                    <span class='path1'></span>
                                    <span class='path2'></span>
                                    <span class='path3'></span>
                                    <span class='path4'></span>
                                    <span class='path5'></span>
                                </i>
                                Hapus
                            </button>
                        </div>";
                    }

                    $html = "<div class='row'>
                        $editHtml $destroyHtml 
                    </div>";

                    return $html;
                },
            ],
            [
                'key' => 'name',
            ],
            [
                'key' => 'no_jp',
            ],
            [
                'key' => 'dob',
            ],
            [
                'key' => 'facebook',
            ],
            [
                'key' => 'instagram',
            ],
            [
                'key' => 'pic',
            ],
            [
                'key' => 'lpk',
            ],
            [
                'key' => 'note',
            ],
        ];
    }

    // public function getQuery(): Builder
    // {
    //     return RegencyRepository::datatable();
    // }

    #[On('getData')]
    public function getData($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
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
        return $query;
    }

    public function getView(): string
    {
        return 'livewire.master-client-registered.datatable-preview';
    }
}
