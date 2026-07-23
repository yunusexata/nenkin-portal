<?php

namespace App\Livewire\MasterClientRegistered;

use App\Models\Client\ClientClosingHistory;
use App\Repositories\Client\ClientClosingHistoryRepository;
use App\Repositories\Client\ClientRepository;
use App\Traits\Livewire\WithDatatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class DatatablePreview extends Component
{
    use WithDatatable;

    public $collection;
    #[Url(except: '')]
    public $selectedBatch;
    public $closingHistories = [];
    public $bulkPic;
    public $filter_claim_type;

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
                'name' => 'Nama Kabupaten/Kota',
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
        if ($this->type == 'fix') {
            if ($this->name == 'registered') {
                return ClientRepository::getClientRegistered(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
            if ($this->name === 'unclosed') {
                return ClientRepository::getClientUnclosing(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
            if ($this->name === 'unconfirmed') {
                return ClientRepository::getClientUnregistered(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
            if ($this->name === 'kokumin') {
                return ClientRepository::getClientKokumin(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                );
            }
        } else {
            return ClientRepository::getClientRegistered(
                $this->selectedBatch,
                $this->filter_claim_type,
                $this->bulkPic,
                $this->name,
                true
            );
        }
        return ClientClosingHistory::query();
    }

    public function getView(): string
    {
        return 'livewire.master-client-registered.datatable-preview';
    }
}
