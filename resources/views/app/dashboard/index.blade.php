@extends('layouts.app')

@section('content')

    @if (Auth::user()->hasRole(\App\Models\User::ROLE_CLIENT) && Auth::user()->client->confirmation)
        <div class="grid grid-cols-12 gap-4 md:gap-6">
            @if(auth()->user()->hasRole(\App\Models\User::ROLE_CLIENT))
            <div class="col-span-12">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Dokumen Saya</h3>
                <p class="text-sm text-gray-500 mb-6">Status ketersediaan dokumen Anda yang diunggah oleh admin</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @include('client.components.document-card', ['type' => 'ichijikin', 'title' => 'Ichijikin', 'document' => $documents['ichijikin'] ?? null])
                @include('client.components.document-card', ['type' => 'resi', 'title' => 'Resi Transfer', 'document' => $documents['resi'] ?? null])
                @include('client.components.document-card', ['type' => 'kwitansi', 'title' => 'Kwitansi', 'document' => $documents['kwitansi'] ?? null])
                </div>
            </div>
            @else
            <div class="col-span-12 bg-white dark:bg-gray-850 p-8 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Dashboard Admin</h3>
                <p class="text-sm text-gray-500">Selamat datang di Portal Nenkin Terpusat. Silakan gunakan menu sidebar untuk mengelola klien dan dokumen.</p>
            </div>
            @endif
        </div>
    @else
        <x-common.component-card class="flex justify-center flex-nowrap">
            
        <div class="w-full max-w-lg">
            <!-- Logo / Branding Header -->
            <div class="text-center mb-6">
                <h1 class="text-3xl font-extrabold text-[#111827] tracking-tight">EXATA <span class="text-[#F97316]">INDONESIA</span></h1>
                <p class="text-gray-600 text-sm mt-1 font-medium">Konfirmasi Status Layanan Pemrosesan Nenkin</p>
            </div>

            @livewire('client.form-confirmation')

            <div class="text-center text-xs text-gray-500 mt-6">
                &copy; 2026 PT Exata Indonesia Group. Seluruh Hak Cipta Dilindungi.
            </div>
        </div>
        </x-common.component-card>
    @endif

@endsection

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('SwalInformation', (event) => {
            // event[1] adalah title yang dikirim via Alert::information()
            // Kita hanya reload jika judulnya "Data berhasil disimpan" (Konfirmasi Sukses)
            const title = Array.isArray(event) ? event[1] : (event?.title || '');
            
            if (title === 'Data berhasil disimpan') {
                setTimeout(() => {
                    location.reload();
                }, 1600); // Reload sedikit setelah swal tayang (timer swal 1500ms)
            }
        });
    });
</script>
@endpush
