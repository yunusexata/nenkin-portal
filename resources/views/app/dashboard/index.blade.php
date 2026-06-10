@extends('layouts.app')

@section('content')

    <!-- Admin Dashboard Stats -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard Ringkasan</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Status dan statistik operasional saat ini.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
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

@endsection

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('SwalInformation', (event) => {
            const title = Array.isArray(event) ? event[1] : (event?.title || '');
            
            if (title === 'Data berhasil disimpan') {
                setTimeout(() => {
                    location.reload();
                }, 1600);
            }
        });
    });
</script>
@endpush
