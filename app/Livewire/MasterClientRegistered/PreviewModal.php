<?php

namespace App\Livewire\MasterClientRegistered;

use App\Helpers\Alert;
use App\Models\Client\Client;
use App\Repositories\Client\ClientClosingHistoryRepository;
use App\Repositories\Client\ClientRepository;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PreviewModal extends Component
{

    public $collection;
    #[Url(except: '')]
    public $selectedBatch;
    public $closingHistories = [];
    public $bulkPic;
    public $filter_claim_type;
    public function mount()
    {
        if ($this->selectedBatch) {
            $this->closingHistories = ClientClosingHistoryRepository::getBy([
                ['source_file_name', $this->selectedBatch]
            ]);
        }
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

        $this->dispatch('open-regular-modal');
        $this->getData('registered', 'fix');
    }

    public function getData($name, $type)
    {
        if ($type === 'fix') {
            if ($name === 'registered') {
                $this->collection = ClientRepository::getClientRegistered(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )->get();
            }
            if ($name === 'unclosed') {
                $this->collection = ClientRepository::getClientUnclosing(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )->get();
            }
            if ($name === 'unconfirmed') {
                $this->collection = ClientRepository::getClientUnregistered(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )->get();
            }
            if ($name === 'kokumin') {
                $this->collection = ClientRepository::getClientKokumin(
                    $this->selectedBatch,
                    $this->filter_claim_type,
                    $this->bulkPic
                )->get();
            }
        }

        if ($type === 'closing') {
            $this->collection = ClientRepository::getClientRegistered(
                $this->selectedBatch,
                $this->filter_claim_type,
                $this->bulkPic,
                $name,
                true
            )->get();
        }
    }

    public function render()
    {
        return view('livewire.master-client-registered.preview-modal');
    }
}
