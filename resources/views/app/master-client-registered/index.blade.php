@extends('layouts.app')

@section('content')
    
    <x-common.component-card title="">
        
        @can(PermissionHelper::transform(PermissionHelper::ACCESS_MASTER_CLIENT_REGISTERED, PermissionHelper::TYPE_UPDATE))
            <div data-slot="card-content" class="group-data-[size=sm]/card:px-3 p-0">
                <x-ui.button size="sm" variant="success" @click="$dispatch('export', { type: '{{ App\Helpers\ExportHelper::TYPE_EXCEL }}' })">Export Excel
                </x-ui.button>
            </div>
        @endCan
        <livewire:master-client-registered.datatable />
    </x-common.component-card>
 
@endsection
