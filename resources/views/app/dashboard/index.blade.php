@extends('layouts.app')

@section('content')

    <!-- Admin Dashboard Stats -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard Ringkasan</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Status dan statistik operasional saat ini.</p>
    </div>
    
    <livewire:dashboard.detail />

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
