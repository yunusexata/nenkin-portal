@extends('layouts.app')

@section('content')
    
    <x-common.component-card title="">
        <livewire:master-client-speed.datatable lazy />
    </x-common.component-card>
 
@endsection
