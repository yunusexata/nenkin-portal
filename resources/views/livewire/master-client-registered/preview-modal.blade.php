
<x-ui.modal x-data="{ open: false }" @open-regular-modal.window="open = true"     @close-regular-modal.window="open = false"
 modalId="importModal" :isOpen="false" class="max-w-[96vw] p-10" wire:ignore.self>
    <div class="relative w-full bg-white dark:bg-gray-900 overflow-y-scroll max-h-[95vw]">
        {{-- Blocking overlay --}}
        <div wire:loading wire:target="getData" class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 z-50 flex items-center justify-center backdrop-blur-[2px]">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 flex flex-col items-center gap-3">
                <svg class="animate-spin h-10 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs text-gray-500 dark:text-gray-400">Sedang mencari data anda ...</span>
            </div>
        </div>

        <h4 class="font-semibold text-gray-800 mb-7 text-title-sm dark:text-white/90">
            Preview Data
        </h4>

        <div class="border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div x-data="{ tab: 'registered' }">

                {{-- Tabs --}}
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">

                        <button
                            type="button"
                            @click="tab = 'registered'"
                            :class="tab === 'registered'
                                ? 'border-brand-500 text-brand-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="border-b-2 px-1 py-4 text-sm font-medium"
                            wire:click="getData('registered', 'fix')"
                        >
                            Data Client Registered
                        </button>
                        @foreach ($closingHistories as $closing)
                            <button
                                type="button"
                                @click="tab = 'closing {{ $closing->batch_number }}'"
                                :class="tab === 'closing {{ $closing->batch_number }}'
                                    ? 'border-red-500 text-red-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="border-b-2 px-1 py-4 text-sm font-medium"
                                wire:click="getData('{{ $closing->batch_number }}', 'closing')"
                            >
                                Closing {{ $closing->batch_number }} {{ $closing->created_at->format('Ymd H_i_s') }}
                            </button>
                        @endforeach
                        <button
                            type="button"
                            @click="tab = 'unclosed'"
                            :class="tab === 'unclosed'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="border-b-2 px-1 py-4 text-sm font-medium"
                            wire:click="getData('unclosed', 'fix')"
                        >
                            Data Belum Closing
                        </button>
                        <button
                            type="button"
                            @click="tab = 'unconfirmed'"
                            :class="tab === 'unconfirmed'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="border-b-2 px-1 py-4 text-sm font-medium"
                            wire:click="getData('unconfirmed', 'fix')"
                        >
                            Data Belum Konfirmasi
                        </button>
                        <button
                            type="button"
                            @click="tab = 'kokumin'"
                            :class="tab === 'kokumin'
                                ? 'border-red-500 text-red-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="border-b-2 px-1 py-4 text-sm font-medium"
                            wire:click="getData('kokumin', 'fix')"
                        >
                            Data Client Kokumin
                        </button>

                    </nav>
                </div>

                {{-- REGISTERED TAB --}}
                <div
                    x-transition
                    class="mt-4"
                >
                    <div class="overflow-x-auto max-h-[450px] overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NO INPUT JPN</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NAMA</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">TGL LAHIR</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NAMA FB</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NAMA IG</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NO TELP</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">PIC</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">LPK</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">NOTE</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">JENIS PENCAIRAN</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">TANDA TANGAN</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">POSISI SAAT INI</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">BULAN RE ENTRY</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">ALASAN RE ENTRY</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">STATUS EKSPOR</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">WAKTU CLOSING</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                @php $no = 1; @endphp
                                @if ($collection)
                                    @foreach ($collection as $index => $data)
                                        @php
                                            /*
                                            * Colour matrix:
                                            * SPEED                                           → green  #00ff3c
                                            * NORMAL + ALASAN RE ENTRY = akan kembali jepang → red    #ff0015
                                            * NORMAL + ALASAN RE ENTRY = sudah di jepang     → orange #ff9900
                                            * ADJUSTMENT                                      → yellow #fffb00
                                            */
                                            $rowColor = '';

                                            $claimType       = $data?->confirmation?->claim_type ?? '';
                                            $currentPosition = $data->confirmation?->current_position;
                                            $backToJpType    = $data->confirmation?->back_to_jp_type;
                                            if(in_array($claimType, [
                                                App\Models\Client\Client::CLAIM_TYPE_SPEED,
                                                App\Models\Client\Client::CLAIM_TYPE_ADJUSTMENT
                                            ]) && !$data?->esign
                                            ){
                                                $rowColor = '#465FFF';
                                            }elseif ($claimType === App\Models\Client\Client::CLAIM_TYPE_SPEED) {
                                                $rowColor = '#00ff3c'; // hijau
                                            } elseif ($claimType === App\Models\Client\Client::CLAIM_TYPE_ADJUSTMENT) {
                                                $rowColor = '#fffb00'; // kuning
                                            } elseif ($claimType === App\Models\Client\Client::CLAIM_TYPE_NORMAL) {
                                                if ($currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_JEPANG) {
                                                    $rowColor = '#ff9900'; // orange — sudah di jepang
                                                } elseif (
                                                    $currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                                                    && $backToJpType === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_YES
                                                ) {
                                                    $rowColor = '#ff0015'; // merah — akan kembali ke jepang
                                                }
                                            }

                                            // WHITE = MATRIKS SPEED/ADJUSMENT NOT ESIGNED

                                            $rowStyle = $rowColor ? "background-color: {$rowColor};" : '';
                                            $rowStyle .='
                                                border:1px solid #000;
                                                padding:4px;
                                                white-space: nowrap;
                                            ';
                                            // Alasan re entry text
                                            
                                            $alasanReEntry = '';
                                            if ($currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_JEPANG) {
                                                $alasanReEntry = 'SUDAH DIJEPANG';
                                            } elseif (
                                                $currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                                                && $backToJpType === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_NO
                                            ) {
                                                $alasanReEntry = 'TIDAK KEMBALI KE JEPANG';
                                            } elseif (
                                                $currentPosition === App\Models\Client\ClientConfirmation::CURRENT_POSITION_INDONESIA
                                                && $backToJpType === App\Models\Client\ClientConfirmation::BACK_TO_JP_TYPE_YES
                                            ) {
                                                $alasanReEntry = 'AKAN KEMBALI KE JEPANG';
                                            }
                                        @endphp
                                        <tr style="{{ $rowStyle }}">
                                            <td style="{{ $rowStyle }}">{{ $loop->iteration }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['no_jp']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['name']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ $data['dob'] ? Carbon\Carbon::parse($data['dob'])->format('Ymd') : '' }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['facebook']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['instagram']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ $data['phone'] }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['pic']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['lpk']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['note']) }}</td>
                                            @php
                                                $claimType = $data?->confirmation?->claim_type;
                                                $text = '';

                                                if (!$data?->confirmation || $claimType === App\Models\Client\Client::CLAIM_TYPE_NORMAL) {
                                                    $text = App\Models\Client\Client::CLAIM_TYPE_NORMAL;
                                                } elseif (in_array($claimType, [App\Models\Client\Client::CLAIM_TYPE_ADJUSTMENT, App\Models\Client\Client::CLAIM_TYPE_SPEED])) {
                                                    // Jika esign true -> pakai claim_type asli, jika false -> PENDING
                                                    $text = $data?->esign ? $claimType : App\Models\Client\Client::CLAIM_TYPE_PENDING;
                                                }
                                            @endphp
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($text) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data['esign_exists'] ? 'Sudah' : 'Belum') }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data->confirmation?->current_position) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($data->confirmation?->departure_month ? Carbon\Carbon::createFromFormat('n-Y', $data->confirmation?->departure_month.'-'.$data->confirmation?->departure_year)->locale('id')->translatedFormat('F Y') : '') }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($alasanReEntry) }}</td>
                                            <td style="{{ $rowStyle }}">{{ $data['is_exported'] ? 'EXPORTED' : 'UN-EXPORTED' }}</td>
                                            <td style="{{ $rowStyle }}">{{ $data->confirmation?->closing?->created_at ? Carbon\Carbon::parse($data->confirmation?->closing?->created_at)->format('Ymd H:i:s') : '' }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-ui.modal>


@push('scripts')
    {{-- <script src="{{ asset('assets/js/jquery.min.js') }}"></script> --}}
    <script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('onSuccessImportModal', () => {

            window.dispatchEvent(
                new CustomEvent('close-regular-modal')
            );
        });
    });
    </script>
@endpush