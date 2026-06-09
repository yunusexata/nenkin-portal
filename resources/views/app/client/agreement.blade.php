@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        @if(auth()->user()->hasRole(\App\Models\User::ROLE_CLIENT))
        <div class="col-span-12">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Surat Perjanjian Saya</h3>
            
        </div
        @endif
    </div>
 
@endsection
