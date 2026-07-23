<?php

namespace App\Livewire\Dashboard;

use App\Models\Client\Client;
use App\Models\Client\ClientConfirmation;
use Carbon\Carbon;
use Livewire\Component;

class Detail extends Component
{

    public $selectedBatch = 'all';
    public $batches = [];

    public $pic_achievements = [];
    public $total_achievements = [];

    public function mount()
    {
        $this->loadBatches();
        $this->getDashboardStats();
        $this->getDashboardStatsPIC();
        // $this->getDashboardStatsPICPending();
    }
    private function getDashboardStatsPIC()
    {
        $this->pic_achievements = Client::query()
            ->leftJoin('users', function ($join) {
                $join->on('users.client_id', '=', 'clients.id')
                    ->whereNull('users.deleted_at');
            })
            ->leftJoin('client_confirmations', function ($join) {
                $join->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->leftJoin('client_esigns', function ($join) {
                $join->on('client_esigns.client_id', '=', 'clients.id')
                    ->whereNull('client_esigns.deleted_at');
            })
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('clients.source_file_name', $this->selectedBatch);
            })
            ->selectRaw("
                clients.pic,
                
                COUNT(DISTINCT clients.id) as total_client,

                COUNT(DISTINCT clients.id) FILTER (
                    WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
                    AND users.id IS NOT NULL
                ) as total_registered,

                COUNT(DISTINCT clients.id) FILTER (
                    WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
                    AND users.id IS NULL
                ) as not_registered,

                COUNT(DISTINCT clients.id) FILTER (
                    WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
                    AND client_confirmations.id IS NULL
                    AND users.id IS NOT NULL
                ) as not_confirmed,
                 
                COUNT(DISTINCT clients.id) FILTER (
                    WHERE client_confirmations.claim_type IN (
                        '" . Client::CLAIM_TYPE_SPEED . "',
                        '" . Client::CLAIM_TYPE_ADJUSTMENT . "'
                    )
                ) as total_speed,

                COUNT(DISTINCT clients.id) FILTER (
                    WHERE client_confirmations.claim_type IN (
                        '" . Client::CLAIM_TYPE_SPEED . "',
                        '" . Client::CLAIM_TYPE_ADJUSTMENT . "'
                    )
                    AND client_esigns.id IS NULL
                ) as pending,

                COUNT(DISTINCT clients.id) FILTER (
                    WHERE clients.claim_type = '" . Client::CLAIM_TYPE_KOKUMIN . "'
                ) as kokumin
            ")
            ->groupBy('clients.pic')
            ->orderBy('clients.pic')
            ->get()
            ->toArray();
    }

    private function getDashboardStats()
    {
        $this->total_achievements = Client::query()
            ->leftJoin('users', function ($join) {
                $join->on('users.client_id', '=', 'clients.id')
                    ->whereNull('users.deleted_at');
            })
            ->leftJoin('client_confirmations', function ($join) {
                $join->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->leftJoin('client_esigns', function ($join) {
                $join->on('client_esigns.client_id', '=', 'clients.id')
                    ->whereNull('client_esigns.deleted_at');
            })
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('clients.source_file_name', $this->selectedBatch);
            })
            ->selectRaw("
            COUNT(DISTINCT clients.id) AS total_clients,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE clients.claim_type = '" . Client::CLAIM_TYPE_KOKUMIN . "'
            ) AS kokumin_total,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
                AND users.id IS NOT NULL
            ) AS registered_total,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE client_confirmations.id IS NULL
                AND clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
                AND users.id IS NOT NULL
            ) AS not_confirmed_total,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
                AND client_confirmations.claim_type IN (
                    '" . Client::CLAIM_TYPE_SPEED . "',
                    '" . Client::CLAIM_TYPE_ADJUSTMENT . "'
                )
                AND client_esigns.id IS NULL
            ) AS pending_total,
            
            COUNT(DISTINCT clients.id) FILTER (
                WHERE client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_JEPANG . "'
            ) AS jpn_total,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE client_confirmations.claim_type = '" . Client::CLAIM_TYPE_NORMAL . "'
                AND client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_INDONESIA . "'
            ) AS re_total,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE client_confirmations.claim_type = '" . Client::CLAIM_TYPE_SPEED . "'
                AND client_esigns.id IS NOT NULL
            ) AS speed_total,

            COUNT(DISTINCT clients.id) FILTER (
                WHERE client_confirmations.claim_type = '" . Client::CLAIM_TYPE_ADJUSTMENT . "'
                AND client_esigns.id IS NOT NULL
            ) AS adjustment_total
        ")
            ->first()->toArray();
    }

    public function selectBatch($batchName)
    {
        $this->selectedBatch = $batchName;

        $this->getDashboardStats();
        $this->getDashboardStatsPIC();
    }

    private function test()
    {
        return 'test';
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

    public function render()
    {
        return view('livewire.dashboard.detail');
    }
}
