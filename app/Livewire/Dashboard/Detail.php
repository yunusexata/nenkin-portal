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
    }
    private function getDashboardStatsPIC()
    {
        $this->pic_achievements = Client::query()
            ->leftJoin('users', 'users.client_id', '=', 'clients.id')
            ->leftJoin('client_confirmations', function ($join) {
                $join->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('clients.source_file_name', $this->selectedBatch);
            })
            ->selectRaw("
        clients.pic,

        COUNT(clients.id) as total_client,

        COUNT(DISTINCT clients.id) FILTER (
            WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
            AND users.id IS NULL
        ) as not_registered,

        COUNT(DISTINCT clients.id) FILTER (
            WHERE clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
            AND client_confirmations.id IS NULL
        ) as not_confirmed,

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
            ->leftJoin('client_confirmations', function ($join) {
                $join->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('clients.source_file_name', $this->selectedBatch);
            })
            ->selectRaw("
            COUNT(clients.id) AS total_clients,

            COUNT(*) FILTER (
                WHERE client_confirmations.id IS NULL
                AND clients.claim_type != '" . Client::CLAIM_TYPE_KOKUMIN . "'
            ) AS not_confirmed_total,

            COUNT(*) FILTER (
                WHERE clients.claim_type = '" . Client::CLAIM_TYPE_KOKUMIN . "'
            ) AS kokumin_total,

            COUNT(*) FILTER (
                WHERE client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_JEPANG . "'
            ) AS jpn_total,

            COUNT(*) FILTER (
                WHERE client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_INDONESIA . "'
                AND client_confirmations.back_to_jp_type = '" . ClientConfirmation::BACK_TO_JP_TYPE_NO . "'
            ) AS speed_total,

            COUNT(*) FILTER (
                WHERE client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_INDONESIA . "'
                AND client_confirmations.back_to_jp_type = '" . ClientConfirmation::BACK_TO_JP_TYPE_YES . "'
                AND make_date(
                    client_confirmations.departure_year::int,
                    client_confirmations.departure_month::int,
                    1
                ) < (
                    clients.created_at::date + interval '6 months'
                )
            ) AS re_total,

            COUNT(*) FILTER (
                WHERE client_confirmations.current_position = '" . ClientConfirmation::CURRENT_POSITION_INDONESIA . "'
                AND client_confirmations.back_to_jp_type = '" . ClientConfirmation::BACK_TO_JP_TYPE_YES . "'
                AND make_date(
                    client_confirmations.departure_year::int,
                    client_confirmations.departure_month::int,
                    1
                ) >= (
                    clients.created_at::date + interval '6 months'
                )
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

        consoleLog($this, [
            $this->pic_achievements,
            $this->total_achievements
        ]);
        return view('livewire.dashboard.detail');
    }
}
