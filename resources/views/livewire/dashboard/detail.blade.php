
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        <div class="w-1/4">
            <!-- Filter by Batch Dropdown Select -->
            <div class="px-5 mb-5 sm:px-6">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Batch:</div>
                
                <div x-data="{ 
                    open: false, 
                    search: '',
                    batches: @entangle('batches'),
                    get filtered() { 
                        if (!this.search) return this.batches;
                        return this.batches.filter(b => 
                            b.name.toLowerCase().includes(this.search.toLowerCase())
                        )
                    }
                }" @click.away="open = false" class="relative inline-block text-left w-full">
                    
                    {{-- Dropdown Trigger Button --}}
                    <div class="relative">
                        <button 
                            type="button" 
                            @click="open = !open"
                            class="flex h-[42px] w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            <span>
                                @if($selectedBatch && $selectedBatch !== 'all')
                                    {{ $selectedBatch }}
                                @else
                                    Pilih Batch...
                                @endif
                            </span>
                            
                            <div class="flex items-center gap-1.5">
                                @if($selectedBatch && $selectedBatch !== 'all')
                                    {{-- Tombol X untuk reset --}}
                                    <span 
                                        @click.stop="$wire.selectBatch('all')" 
                                        class="material-symbols-outlined text-gray-400 hover:text-gray-600 dark:hover:text-white text-lg cursor-pointer"
                                    >
                                        close
                                    </span>
                                @endif
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7 10l3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </button>
                    </div>

                    {{-- Dropdown Menu --}}
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 mt-2 z-50 w-full rounded-lg bg-white shadow-lg border border-gray-200 dark:bg-gray-900 dark:border-gray-800 py-1"
                        style="display: none;"
                    >
                        {{-- Search Input inside Dropdown --}}
                        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800">
                            <input 
                                x-model="search" 
                                type="text" 
                                placeholder="Cari nama batch..." 
                                class="h-[36px] w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-xs text-gray-800 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none dark:border-gray-800 dark:bg-gray-950 dark:text-white"
                                @click.stop
                            >
                        </div>

                        {{-- Batches List --}}
                        <div class="max-h-[240px] overflow-y-auto custom-scrollbar py-1">
                            <template x-for="batch in filtered" :key="batch.name">
                                <button
                                    type="button"
                                    @click="open = false; $wire.selectBatch(batch.name)"
                                    class="flex w-full items-center justify-between px-4 py-2.5 text-xs text-left text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <span class="font-medium truncate mr-2" x-text="batch.name"></span>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 whitespace-nowrap" x-text="`(${Number(batch.total).toLocaleString()} klien)`"></span>
                                </button>
                            </template>

                            <div x-show="filtered.length === 0" class="px-4 py-3 text-xs text-center text-gray-400">
                                Tidak ada batch ditemukan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 1: Total Klien -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Klien</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ number_format($stats['total_clients']) }}</h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-50 text-brand-blue rounded-xl dark:bg-brand-blue/10">
                    <svg class="fill-current w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 2: Klien Speed/Adjustment -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Klien Speed / Adjustment</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ number_format($stats['speed_clients']) }}</h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-orange-50 text-brand-orange rounded-xl dark:bg-brand-orange/10">
                    <svg class="fill-current w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 3: Dokumen Terproses -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Dokumen Terproses</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ number_format($stats['total_documents']) }}</h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-50 text-success-600 rounded-xl dark:bg-success-600/10">
                    <svg class="fill-current w-6 h-6" viewBox="0 0 24 24">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 4: Review Pending -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Review Pending</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ number_format($stats['pending_reviews']) }}</h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-red-50 text-error-600 rounded-xl dark:bg-error-600/10">
                    <svg class="fill-current w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>