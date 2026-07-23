
    <div class="rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <!-- Header -->
        <div class="mb-4 flex flex-col gap-4 px-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                
                <div class="flex items-center gap-2 {{ !isset($show_filter) || $show_filter == true ? '' : 'hidden' }}">
                    <label class="text-sm font-medium text-black dark:text-white shrink-0">
                        Show
                    </label>
                    <div class="relative z-20 bg-transparent min-w-[80px] dark:bg-form-input">
                        <select 
                            wire:model.live.change="length" 
                            class="relative z-20 w-full appearance-none rounded-lg border border-gray-300 bg-transparent pl-4 pr-10 h-[42px] text-sm text-black outline-none transition focus:border-primary active:border-primary dark:border-gray-700 dark:text-white"
                        >
                            @foreach ($lengthOptions as $item)
                                <option value="{{ $item }}" class="text-body dark:bg-boxdark">{{ $item }}</option>
                            @endforeach
                        </select>
                        
                        <span class="absolute right-3 top-1/2 z-30 -translate-y-1/2 text-body pointer-events-none">
                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.29289 8.29289C5.68342 7.90237 6.31658 7.90237 6.70711 8.29289L12 13.5858L17.2929 8.29289C17.6834 7.90237 18.3166 7.90237 18.7071 8.29289C19.0976 8.68342 19.0976 9.31658 18.7071 9.70711L12.7071 15.7071C12.3166 16.0976 11.6834 16.0976 11.2929 15.7071L5.29289 9.70711C4.90237 9.31658 4.90237 8.68342 5.29289 8.29289Z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <form onsubmit="event.preventDefault();" class="m-0">
                    <div class="relative">
                        <button type="button" class="absolute -translate-y-1/2 left-4 top-1/2">
                            <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z" fill=""/>
                            </svg>
                        </button>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="h-[42px] w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-[42px] pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-blue-800 xl:w-[300px]"/>
                    </div>
                </form>
                
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden">
            <div class="w-full overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
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
                                @if ($data)
                                    @foreach ($data as $index => $item)
                                        @php
                                            /*
                                            * Colour matrix:
                                            * SPEED                                           → green  #00ff3c
                                            * NORMAL + ALASAN RE ENTRY = akan kembali jepang → red    #ff0015
                                            * NORMAL + ALASAN RE ENTRY = sudah di jepang     → orange #ff9900
                                            * ADJUSTMENT                                      → yellow #fffb00
                                            */
                                            $rowColor = '';

                                            $claimType       = $item?->confirmation?->claim_type ?? '';
                                            $currentPosition = $item->confirmation?->current_position;
                                            $backToJpType    = $item->confirmation?->back_to_jp_type;
                                            if(in_array($claimType, [
                                                App\Models\Client\Client::CLAIM_TYPE_SPEED,
                                                App\Models\Client\Client::CLAIM_TYPE_ADJUSTMENT
                                            ]) && !$item?->esign
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
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['no_jp']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['name']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ $item['dob'] ? Carbon\Carbon::parse($item['dob'])->format('Ymd') : '' }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['facebook']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['instagram']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ $item['phone'] }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['pic']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['lpk']) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['note']) }}</td>
                                            @php
                                                $claimType = $item?->confirmation?->claim_type;
                                                $text = '';

                                                if (!$item?->confirmation || $claimType === App\Models\Client\Client::CLAIM_TYPE_NORMAL) {
                                                    $text = App\Models\Client\Client::CLAIM_TYPE_NORMAL;
                                                } elseif (in_array($claimType, [App\Models\Client\Client::CLAIM_TYPE_ADJUSTMENT, App\Models\Client\Client::CLAIM_TYPE_SPEED])) {
                                                    // Jika esign true -> pakai claim_type asli, jika false -> PENDING
                                                    $text = $item?->esign ? $claimType : App\Models\Client\Client::CLAIM_TYPE_PENDING;
                                                }
                                            @endphp
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($text) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item['esign_exists'] ? 'Sudah' : 'Belum') }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item->confirmation?->current_position) }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($item->confirmation?->departure_month ? Carbon\Carbon::createFromFormat('n-Y', $item->confirmation?->departure_month.'-'.$item->confirmation?->departure_year)->locale('id')->translatedFormat('F Y') : '') }}</td>
                                            <td style="{{ $rowStyle }}">{{ mb_strtoupper($alasanReEntry) }}</td>
                                            <td style="{{ $rowStyle }}">{{ $item['is_exported'] ? 'EXPORTED' : 'UN-EXPORTED' }}</td>
                                            <td style="{{ $rowStyle }}">{{ $item->confirmation?->closing?->created_at ? Carbon\Carbon::parse($item->confirmation?->closing?->created_at)->format('Ymd H:i:s') : '' }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
            </div>
        </div>

        
        {{ $data->links(data: ['scrollTo' => false]) }}
        <!-- Pagination -->
        {{-- <div class="px-6 py-4 border-t border-gray-200 dark:border-white/[0.05]">
            <div class="flex items-center justify-between">
                <button @click="prevPage" :disabled="currentPage === 1" :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''" class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:px-3.5">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.58301 9.99868C2.58272 10.1909 2.65588 10.3833 2.80249 10.53L7.79915 15.5301C8.09194 15.8231 8.56682 15.8233 8.85981 15.5305C9.15281 15.2377 9.15297 14.7629 8.86018 14.4699L5.14009 10.7472L16.6675 10.7472C17.0817 10.7472 17.4175 10.4114 17.4175 9.99715C17.4175 9.58294 17.0817 9.24715 16.6675 9.24715L5.14554 9.24715L8.86017 5.53016C9.15297 5.23717 9.15282 4.7623 8.85983 4.4695C8.56684 4.1767 8.09197 4.17685 7.79917 4.46984L2.84167 9.43049C2.68321 9.568 2.58301 9.77087 2.58301 9.99715C2.58301 9.99766 2.58301 9.99817 2.58301 9.99868Z" fill="currentColor"/>
                    </svg>
                    <span class="hidden sm:inline">Previous</span>
                </button>

                <span class="block text-sm font-medium text-gray-700 dark:text-gray-400 sm:hidden">
                    Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                </span>

                <ul class="hidden items-center gap-0.5 sm:flex">
                    <template x-for="page in displayedPages" :key="page">
                        <li>
                            <button x-show="page !== '...'" @click="goToPage(page)" :class="currentPage === page ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-blue-500/[0.08] hover:text-blue-500 dark:text-gray-400 dark:hover:text-blue-500'" class="flex h-10 w-10 items-center justify-center rounded-lg text-theme-sm font-medium" x-text="page"></button>
                            <span x-show="page === '...'" class="flex h-10 w-10 items-center justify-center text-gray-500">...</span>
                        </li>
                    </template>
                </ul>

                <button @click="nextPage" :disabled="currentPage === totalPages" :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''" class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:px-3.5">
                    <span class="hidden sm:inline">Next</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.4175 9.9986C17.4178 10.1909 17.3446 10.3832 17.198 10.53L12.2013 15.5301C11.9085 15.8231 11.4337 15.8233 11.1407 15.5305C10.8477 15.2377 10.8475 14.7629 11.1403 14.4699L14.8604 10.7472L3.33301 10.7472C2.91879 10.7472 2.58301 10.4114 2.58301 9.99715C2.58301 9.58294 2.91879 9.24715 3.33301 9.24715L14.8549 9.24715L11.1403 5.53016C10.8475 5.23717 10.8477 4.7623 11.1407 4.4695C11.4336 4.1767 11.9085 4.17685 12.2013 4.46984L17.1588 9.43049C17.3173 9.568 17.4175 9.77087 17.4175 9.99715C17.4175 9.99763 17.4175 9.99812 17.4175 9.9986Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div> --}}
    </div>
