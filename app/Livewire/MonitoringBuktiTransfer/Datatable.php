<?php

namespace App\Livewire\MonitoringBuktiTransfer;

use App\Helpers\PermissionHelper;
use App\Models\Client\Client;
use App\Models\Client\ClientConfirmation;
use App\Traits\Livewire\WithDatatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\Component;

class Datatable extends Component
{
    use WithDatatable;

    public $isCanGetAll = false;

    #[Url(except: '')]
    public $documentType = 'all';   // all | resi | kwitansi | ichijikin | general

    #[Url(except: '')]
    public $selectedBatch = 'all';  // source_file_name (batch import client)

    #[Url(except: '')]
    public $selectedClosing = 'all'; // client_closing_batch_number

    #[Url(except: '')]
    public $filterStatus = 'all';   // all | downloaded | seeded | not_downloaded

    public $batches = [];
    public $closings = [];

    public function updatingSelectedBatch() { $this->resetPage(); }
    public function updatingSelectedClosing() { $this->resetPage(); }
    public function updatingDocumentType() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public function onMount()
    {
        // PIC scoping: sales = pic sendiri; all-pic jika punya permission
        $this->isCanGetAll = auth()->user()->hasPermissionTo(
            PermissionHelper::transform(PermissionHelper::ACCESS_FILTER_ALL_PIC, PermissionHelper::TYPE_READ)
        );

        $this->batches = Client::select('source_file_name as name', DB::raw('count(*) as total'))
            ->whereNotNull('source_file_name')
            ->groupBy('source_file_name')
            ->orderByDesc(DB::raw('MAX(imported_at)'))
            ->get()
            ->toArray();

        $this->closings = DB::table('client_confirmations')
            ->select('client_closing_batch_number as name', DB::raw('count(*) as total'))
            ->whereNotNull('client_closing_batch_number')
            ->whereNull('deleted_at')
            ->groupBy('client_closing_batch_number')
            ->orderBy('client_closing_batch_number')
            ->get()
            ->map(fn($item) => (array)$item)
            ->toArray();
    }

    public function getColumns(): array
    {
        return [
            ['key' => 'clients.no_jp', 'name' => 'No Input JPN'],
            ['key' => 'clients.name', 'name' => 'Nama Klien'],
            ['key' => 'clients.pic', 'name' => 'PIC'],
            ['key' => 'clients.source_file_name', 'name' => 'Batch Upload'],
            ['key' => 'client_confirmations.client_closing_batch_number', 'name' => 'Kloter Closing'],
            ['key' => 'document_type', 'name' => 'Tipe Dokumen', 'sortable' => false],
            ['key' => 'status', 'name' => 'Status Download', 'sortable' => false],
            ['key' => 'last_downloaded_at', 'name' => 'Waktu Download', 'sortable' => false],
        ];
    }

    public function getQuery(): Builder
    {
        $pic = auth()->user()->pic;

        // INNER JOIN documents: hanya menampilkan client yang SUDAH punya dokumen bukti transfer.
        $query = Client::query()
            ->select(
                'clients.id',
                'clients.no_jp',
                'clients.name',
                'clients.pic',
                'clients.source_file_name',
                'client_confirmations.client_closing_batch_number',
                'client_confirmations.closed_at',
                DB::raw("MAX(CASE WHEN documents.type IS NOT NULL THEN documents.type END) as document_type"),
                DB::raw("MAX(dd_meta.downloaded_at) as last_downloaded_at"),
                // Status dihitung di SELECT dan dipakai untuk display + export
                DB::raw("CASE
                            WHEN MAX(CASE WHEN dd_meta.source = 'live_tracked' THEN 1 ELSE 0 END) = 1 THEN 'downloaded'
                            WHEN MAX(CASE WHEN dd_meta.source IS NOT NULL THEN 1 ELSE 0 END) = 1 THEN 'seeded'
                            ELSE 'not_downloaded'
                         END as status")
            )
            ->join('documents', function ($j) {
                $j->on('documents.client_id', '=', 'clients.id')
                  ->whereNull('documents.deleted_at');
                if ($this->documentType && $this->documentType !== 'all') {
                    $j->where('documents.type', $this->documentType);
                }
            })
            ->leftJoin('client_confirmations', function ($j) {
                $j->on('client_confirmations.client_id', '=', 'clients.id')
                  ->whereNull('client_confirmations.deleted_at');
            })
            // Sub-query downloads (source-based, filtered by doc type)
            ->leftJoinSub(
                $this->downloadsSubquery(),
                'dd_meta',
                'dd_meta.client_id',
                '=',
                'clients.id'
            )
            ->whereNull('clients.deleted_at');

        // Server-side PIC scoping
        $query->where(function ($q) use ($pic) {
            if (!$this->isCanGetAll) {
                $q->where('clients.pic', $pic);
            }
        });

        if ($this->selectedBatch && $this->selectedBatch !== 'all') {
            $query->where('clients.source_file_name', $this->selectedBatch);
        }
        if ($this->selectedClosing && $this->selectedClosing !== 'all') {
            $query->where('client_confirmations.client_closing_batch_number', $this->selectedClosing);
        }

        $query->groupBy(
            'clients.id', 'clients.no_jp', 'clients.name', 'clients.pic',
            'clients.source_file_name', 'client_confirmations.client_closing_batch_number',
            'client_confirmations.closed_at'
        );

        return $this->applyStatusFilter($query);
    }

    private function downloadsSubquery()
    {
        $sub = DB::table('document_downloads')
            ->select(
                'client_id',
                'document_id',
                'batch_id',
                'source',
                DB::raw('MAX(downloaded_at) as downloaded_at')
            )
            ->whereIn('source', ['live_tracked', 'proof_high', 'notified_only', 'infra_log']);

        if ($this->documentType && $this->documentType !== 'all') {
            $sub->whereExists(function ($w) {
                $w->select(DB::raw(1))
                    ->from('documents')
                    ->whereColumn('documents.id', 'document_downloads.document_id')
                    ->where('documents.type', $this->documentType);
            });
        }

        return $sub->groupBy('client_id', 'document_id', 'batch_id', 'source');
    }

    private function applyStatusFilter(Builder $query): Builder
    {
        // Keterbatasan PostgreSQL: alias 'status' dari SELECT tidak bisa dirujuk langsung di HAVING
        return $query->havingRaw(match ($this->filterStatus) {
            'downloaded'     => "COALESCE(MAX(CASE WHEN dd_meta.source = 'live_tracked' THEN 1 ELSE 0 END), 0) = 1",
            'seeded'         => "COALESCE(MAX(CASE WHEN dd_meta.source = 'live_tracked' THEN 1 ELSE 0 END), 0) = 0
                                 AND COALESCE(MAX(CASE WHEN dd_meta.source IS NOT NULL THEN 1 ELSE 0 END), 0) = 1",
            'not_downloaded' => "COALESCE(MAX(CASE WHEN dd_meta.source IS NOT NULL THEN 1 ELSE 0 END), 0) = 0",
            default          => '1=1',
        });
    }

    #[On('export')]
    public function export($type)
    {
        $fileName = "Monitoring Bukti Transfer " . \Carbon\Carbon::now()->format('Y-m-d H_i_s');
        $query = $this->datatableGetProcessedQuery();

        $request = [
            'title' => 'Monitoring Bukti Transfer',
            'type' => $type,
            'documentType' => $this->documentType,
        ];

        return \App\Helpers\ExportHelper::export(
            $type,
            $fileName,
            $query,
            'app.monitoring-bukti-transfer.export',
            $request
        );
    }

    public function getTotals()
    {
        $allRecords = $this->datatableGetProcessedQuery()->get();

        $totals = [
            'total_downloaded' => 0,
            'total_not_downloaded' => 0,
            'total_ai' => 0,
            'total_mt' => 0,
            'total_sn' => 0,
        ];

        foreach ($allRecords as $row) {
            if ($row->status === 'downloaded' || $row->status === 'seeded') {
                $totals['total_downloaded']++;
            } else {
                $totals['total_not_downloaded']++;
            }

            if ($row->pic === 'ainul') $totals['total_ai']++;
            if ($row->pic === 'kim') $totals['total_mt']++;
            if ($row->pic === 'slamet') $totals['total_sn']++;
        }

        return $totals;
    }

    public function render()
    {
        return view($this->getView(), [
            'data' => $this->datatableGetData(),
            'columns' => $this->getColumns(),
            'totals' => $this->getTotals(),
        ]);
    }

    public function getView(): string
    {
        return 'livewire.monitoring-bukti-transfer.datatable';
    }
}
