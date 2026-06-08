<?php

use App\Http\Controllers\MasterClient\MasterClientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'access_permission'])->group(function () {
    Route::group(["controller" => MasterClientController::class, "prefix" => "master_client", "as" => "master_client."], function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('{id}/edit', 'edit')->name('edit');
        Route::get('{id}/detail', 'detail')->name('detail');
    });
});
Route::group(["controller" => MasterClientController::class, "prefix" => "master_client", "as" => "master_client."], function () {
    Route::get('/', 'index')->name('index');
    Route::get('create', 'create')->name('create');
    Route::get('{id}/edit', 'edit')->name('edit');
    Route::get('{id}/detail', 'detail')->name('detail');
});
