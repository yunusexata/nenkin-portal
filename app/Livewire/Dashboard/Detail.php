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

    public $total_clients;
    public $jpn_total;
    public $re_total;
    public $speed_total;
    public $adjusment_total;
    public $kokumin_total;

    public function mount()
    {

        // Admin / Internal dashboard
        $limitDate = now()->addMonths(6)->format('Y-m-01');

        $this->loadBatches();
    }
    public static function getDashboardStats()
    {
        return $statsByPic = Client::query()
            ->leftJoin('users', 'users.client_id', '=', 'clients.id')
            ->leftJoin('client_confirmations', function ($join) {
                $join->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->selectRaw("
        clients.pic,

        COUNT(DISTINCT clients.id) as total_client,

        COUNT(DISTINCT clients.id) FILTER (
            WHERE users.id IS NULL
        ) as belum_registrasi,

        COUNT(DISTINCT clients.id) FILTER (
            WHERE client_confirmations.id IS NULL
        ) as belum_konfirmasi
    ")
            ->groupBy('clients.pic')
            ->orderBy('clients.pic')
            ->get();
        return Client::query()
            ->leftJoin('client_confirmations', function ($join) {
                $join->on('client_confirmations.client_id', '=', 'clients.id')
                    ->whereNull('client_confirmations.deleted_at');
            })
            ->selectRaw("
            COUNT(clients.id) AS total_clients,

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
            ->first();
    }

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

    private function test()
    {
        return 'test';
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

    public function render()
    {

        consoleLog($this, $this->getDashboardStats()->toArray());
        return view('livewire.dashboard.detail');
    }
}
