<div class="">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        <div class="w-auto">
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
                                    Seluruh Batch...
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
    </div>
    <div class="my-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Detail Data Klien</h1>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        
        <div class="rounded-2xl border border-blue-100 bg-blue-500 p-5 dark:border-blue-900/40 dark:bg-blue-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-blue-400 font-semibold uppercase tracking-wider">Total Klien</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['total_clients']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-blue-500 w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-cyan-100 bg-[#FF00FE] p-5 dark:border-cyan-900/40 dark:bg-cyan-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-cyan-400 font-semibold uppercase tracking-wider">KOKUMIN</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['kokumin_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-[#FF00FE] w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-pink-500 p-5 dark:border-rose-900/40 dark:bg-rose-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-rose-400 font-semibold uppercase tracking-wider">BELUM KONFIRMASI</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['not_confirmed_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-pink-500 w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-green-500 p-5 dark:border-emerald-900/40 dark:bg-emerald-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-emerald-400 font-semibold uppercase tracking-wider">SUDAH KONFIRMASI</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['total_clients'] - $total_achievements['not_confirmed_total']  - $total_achievements['kokumin_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-green-500 w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="my-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Detail Sudah Konfirmasi</h1>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        
        <div class="rounded-2xl border border-indigo-100 bg-[#FE9900] p-5 dark:border-indigo-900/40 dark:bg-indigo-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-indigo-400 font-semibold uppercase tracking-wider">JPN</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['jpn_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-[#FE9900] w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-100 bg-[#FE0000] p-5 dark:border-amber-900/40 dark:bg-amber-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-amber-400 font-semibold uppercase tracking-wider">RE - ENTRY</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['re_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-[#FE0000] w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-violet-100 bg-[#00FF01] p-5 dark:border-violet-900/40 dark:bg-violet-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-violet-400 font-semibold uppercase tracking-wider">SPEED</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['speed_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-[#00FF01] w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-fuchsia-100 bg-[#FFDF20] p-5 dark:border-fuchsia-900/40 dark:bg-fuchsia-950/10 md:p-6 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-white dark:text-fuchsia-400 font-semibold uppercase tracking-wider">ADJUSTMENT</span>
                    <h4 class="mt-2 font-bold text-white text-title-sm dark:text-white">
                        {{ number_format($total_achievements['adjustment_total']) }}
                    </h4>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-white text-white rounded-xl shadow-sm">
                    <svg class="fill-[#FFDF20] w-6 h-6" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="my-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Detail Data PIC Registrasi</h1>
    </div>
    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                
                <thead>
                    <tr class="bg-gray-2 text-left dark:bg-meta-4 border-b border-stroke dark:border-strokedark">
                        <th class="text-center min-w-[150px] px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider">
                            PIC
                        </th>
                        <th class="text-center px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider  hidden sm:table-cell">
                            Belum Buat Akun
                        </th>
                        <th class="text-center px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider  hidden sm:table-cell">
                            Sudah Buat Akun
                        </th>
                        <th class="text-center px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider  hidden sm:table-cell">
                            KOKUMIN
                        </th>
                        <th class="text-center px-6 py-4.5 font-extrabold uppercase text-primary dark:text-white text-lg tracking-wider ">
                            Total
                        </th>
                    </tr>
                </thead>

                @php
                    $total_pic_not_registered = 0;
                    $total_pic_kokumin = 0;
                    $total_pic_total = 0;
                @endphp
                
                <tbody class="bg-white dark:bg-boxdark">
                    @foreach ($pic_achievements as $item)
                    @php
                        $total_pic_not_registered += $item['not_registered'];
                        $total_pic_kokumin += $item['kokumin'];
                        $total_pic_total += $item['total_client'];
                    @endphp
                        <tr class="border-b border-stroke dark:border-strokedark bg-gradient-to-r from-orange-50/30 to-transparent dark:from-meta-4/20 transition hover:bg-gray-50 dark:hover:bg-meta-4/40">
                            <td class="text-center px-6 py-5">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-xl font-bold text-black dark:text-white">
                                        {{ strtoupper($item['pic']) }}
                                    </h3>
                                </div>
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl font-semibold text-red-500 dark:text-white hidden sm:table-cell">
                                {{ number_format($item['not_registered']) }}
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl font-semibold text-green-500 dark:text-white hidden sm:table-cell">
                                {{ number_format($item['total_client'] - $item['kokumin'] - $item['not_registered']) }}
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl text-blue-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($item['kokumin']) }}
                            </td>
                            <td class="text-center px-6 py-5 text-3xl font-black text-meta-3 tracking-tight">
                                {{ number_format($item['total_client']) }}
                            </td>
                            
                        </tr>
                    @endforeach
                        <tr class="border-t-2 border-slate-300 dark:border-slate-600 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900">
                            <td class="text-center px-6 py-5">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-xl font-bold text-black dark:text-white">
                                        TOTAL
                                    </h3>
                                </div>
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl font-semibold text-red-500 dark:text-white hidden sm:table-cell">
                                {{ number_format($total_pic_not_registered) }}
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl font-semibold text-green-500 dark:text-white hidden sm:table-cell">
                                {{ number_format($total_pic_total - $total_pic_kokumin - $total_pic_not_registered) }}
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl text-blue-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($total_pic_kokumin) }}
                            </td>
                            <td class="text-center px-6 py-5 text-3xl font-black text-meta-3 tracking-tight">
                                {{ number_format($total_pic_total) }}
                            </td>
                            
                        </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="my-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Detail Data PIC Konfirmasi</h1>
    </div>
    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                
                <thead>
                    <tr class="bg-gray-2 text-left dark:bg-meta-4 border-b border-stroke dark:border-strokedark">
                        <th class="text-center min-w-[150px] px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider">
                            PIC
                        </th>
                        <th class="text-center px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider  hidden sm:table-cell">
                            Belum Konfirmasi
                        </th>
                        <th class="text-center px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider  hidden sm:table-cell">
                            Sudah Konfirmasi
                        </th>
                        <th class="text-center px-6 py-4.5 font-bold uppercase text-black dark:text-white text-base tracking-wider  hidden sm:table-cell">
                            KOKUMIN
                        </th>
                        <th class="text-center px-6 py-4.5 font-extrabold uppercase text-primary dark:text-white text-lg tracking-wider ">
                            Total
                        </th>
                    </tr>
                </thead>

                @php
                    $total_pic_not_confirmed = 0;
                    $total_pic_kokumin = 0;
                    $total_pic_total = 0;
                @endphp
                
                <tbody class="bg-white dark:bg-boxdark">
                    @foreach ($pic_achievements as $item)
                    @php
                        $total_pic_not_confirmed += $item['not_confirmed'];
                        $total_pic_kokumin += $item['kokumin'];
                        $total_pic_total += $item['total_client'];
                    @endphp
                        <tr class="border-b border-stroke dark:border-strokedark bg-gradient-to-r from-orange-50/30 to-transparent dark:from-meta-4/20 transition hover:bg-gray-50 dark:hover:bg-meta-4/40">
                            <td class="text-center px-6 py-5">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-xl font-bold text-black dark:text-white">
                                        {{ strtoupper($item['pic']) }}
                                    </h3>
                                </div>
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl text-red-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($item['not_confirmed']) }}
                            </td>
                            <td class="text-center px-6 py-5 text-2xl text-green-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($item['total_client'] - $item['kokumin'] - $item['not_confirmed']) }}
                            </td>
                            <td class="text-center px-6 py-5 text-2xl text-blue-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($item['kokumin']) }}
                            </td>
                            <td class="text-center px-6 py-5 text-3xl font-black text-meta-3 tracking-tight">
                                {{ number_format($item['total_client']) }}
                            </td>
                            
                        </tr>
                    @endforeach
                        <tr class="border-t-2 border-slate-300 dark:border-slate-600 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900">
                            <td class="text-center px-6 py-5">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-xl font-bold text-black dark:text-white">
                                        TOTAL
                                    </h3>
                                </div>
                            </td>
                            
                            <td class="text-center px-6 py-5 text-2xl text-red-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($total_pic_not_confirmed) }}
                            </td>
                            <td class="text-center px-6 py-5 text-2xl text-green-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($total_pic_total - $total_pic_kokumin - $total_pic_not_confirmed) }}
                            </td>
                            <td class="text-center px-6 py-5 text-2xl text-blue-500 font-semibold text-meta-1 hidden sm:table-cell">
                                {{ number_format($total_pic_kokumin) }}
                            </td>
                            <td class="text-center px-6 py-5 text-3xl font-black text-meta-3 tracking-tight">
                                {{ number_format($total_pic_total) }}
                            </td>
                            
                        </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>