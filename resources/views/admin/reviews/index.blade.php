@extends('layouts.app')

@section('content')
    <div class="col-span-12">
        <div class="mb-6">
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Manajemen Ulasan</h3>
            <p class="text-sm text-gray-500">Kelola dan selesaikan ulasan kepuasan klien yang terintercepted</p>
        </div>
        
        <livewire:admin.review-table />
    </div>
@endsection
