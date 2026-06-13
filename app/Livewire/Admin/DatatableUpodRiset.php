<?php

namespace App\Livewire\Admin;

use App\Helpers\Alert;
use App\Helpers\ExportHelper;
use App\Helpers\PermissionHelper;
use App\Models\Client\Client;
use App\Models\User;
use App\Repositories\Account\UserRepository;
use App\Repositories\Admin\UpodSurveyResponseRepository;
use App\Repositories\Client\ClientRepository;
use App\Traits\Livewire\WithDatatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\On;
use Livewire\Component;

class DatatableUpodRiset extends Component
{
    use WithDatatable;

    public $status;
    public $selectedBatch = 'all';
    public $batches = [];
    public $bulkPic;

    public $batch_name;
    public $filter_claim_type;

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
        $this->loadBatches();
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
        $columns = [
            [
                'name' => 'Nama Klien',
                'render' => function ($item) {
                    return $item->name;
                }
            ],
            [
                'name' => 'PIC',
                'render' => function ($item) {
                    return $item->pic;
                }
            ],
            [
                'key' => 'claim_type',
                'name' => 'Jenis Pencairan',
                'render' => function ($item) {
                    $style = 'primary';
                    switch ($item->claim_type) {
                        case Client::CLAIM_TYPE_ADJUSTMENT:
                            $style = 'amber';
                            break;
                        case Client::CLAIM_TYPE_NORMAL:
                            $style = 'blue';
                            break;
                        case Client::CLAIM_TYPE_SPEED:
                            $style = 'success';
                            break;

                        default:
                            # code...
                            break;
                    }
                    return '<span class="bg-' . $style . '-50 dark:bg-' . $style . '-500/15 text-' . $style . '-600 dark:text-' . $style . '-500 text-theme-xs rounded-full px-2 py-0.5 font-medium">' . strtoupper($item->claim_type) . '</span>';
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Berangkat dari LPK / SO / PT?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q1;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Tahu Exata dari mana?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q2;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Kenapa memutuskan untuk urus nenkin di Exata?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q3;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Apakah kamu pernah dapat penawaran dari konsultan nenkin lain?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q4;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Jika Ya, apa nama konsultan nenkin lain tersebut?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q5;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Jika Ya, kenapa tetap memilih Exata dan bukan konsultan nenkin lain?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q6;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Menurut kamu, hal apa yang paling disukai dari Exata Indonesia?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q7;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Dari segi "Pemberkasan dokumen, informasi/edukasi, dan responsibility staff kami" — apa yang harus kami perbaiki agar pelayanan sesuai harapan?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q8;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Apakah kamu bersedia merekomendasikan Exata Indonesia ke teman/kohai?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q9;
                }
            ],
            [
                'searchable' => false,
                'sortable' => false,
                'name' => 'Jika MERCY terkenal sebagai mobil Mewah, Elegan, untuk Kaum Berkelas, dan IPHONE terkenal sebagai HP Eksklusif, Keren, Cepat, dan Kamera Bagus — maka bagaimana kamu mendeskripsikan Exata Indonesia dalam beberapa kata?',
                'render' => function ($item) {
                    return $item->upodSurveyResponse->response->q10;
                }
            ],
        ];

        return $columns;
    }


    #[On('export')]
    public function export($type)
    {
        $fileName = "Data ClUlasanient Registered " . Carbon::now()->format('Y-m-d H:i:s');
        return ExportHelper::export(
            $type,
            $fileName,
            ClientRepository::getClientUpodSurvey($this->batch_name, $this->filter_claim_type, $this->bulkPic),
            'admin.reviews.export',
            [
                'title' => 'Data Ulasan',
                'type' => $type,
            ],
            [
                'size' => 'legal',
                'orientation' => 'landscape',
            ]
        );
    }

    public function getQuery(): Builder
    {
        return \App\Models\Client\Client::query()
            ->select(
                'clients.*',
            )
            ->whereHas('upodSurveyResponse')
            ->when($this->selectedBatch != 'all', function ($q) {
                $q->where('source_file_name', $this->selectedBatch);
            })
            ->when($this->bulkPic, function ($q) {
                $q->where('pic', strtolower($this->bulkPic));
            })
            ->when($this->filter_claim_type, function ($q) {
                $q->where('clients.claim_type', $this->filter_claim_type);
            });
    }

    public function getView(): string
    {
        return 'livewire.admin.datatable-upod-riset';
    }
}
