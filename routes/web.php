<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Models\Gensen\GensenExportImportHistory;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group([], __DIR__ . '/web/Auth.php');
Route::group([], __DIR__ . '/web/Other.php');
Route::group([], __DIR__ . '/web/Account.php');
Route::group([], __DIR__ . '/web/MasterClient.php');


Route::group(["controller" => DashboardController::class, "prefix" => "dashboard", "as" => "dashboard."], function () {
    Route::get('/', 'index')->name('index');
});
Route::middleware(['auth', 'access_permission'])->group(function () {});

// Client Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/documents/{type}/download', [\App\Http\Controllers\Client\DocumentController::class, 'download'])->name('documents.download');
    Route::get('/esign', [\App\Http\Controllers\Client\EsignController::class, 'show'])->name('client.esign.show');
    Route::post('/esign', [\App\Http\Controllers\Client\EsignController::class, 'store'])->name('client.esign.store');
});

// Admin Routes (Upload & Resolve Documents)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/documents', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'index'])->name('admin_upload_document.index');
    Route::post('/admin/documents/reference', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'uploadExcelReference'])->name('admin.documents.uploadReference');
    Route::post('/admin/documents/clear-reference', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'clearReference'])->name('admin.documents.clearReference');
    Route::post('/admin/documents/upload', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'upload'])->name('admin.documents.upload');
    Route::get('/admin/documents/preview-matching/{temp_batch_id}', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'previewMatching'])->name('admin.documents.previewMatching');
    Route::post('/admin/documents/preview-assign', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'assignPreviewFailure'])->name('admin.documents.previewAssign');
    Route::post('/admin/documents/confirm-upload', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'confirmAndUpload'])->name('admin.documents.confirmUpload');

    Route::get('/admin/documents/batches/{id}', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'batchDetail'])->name('admin.documents.batchDetail');
    Route::get('/admin/documents/batches/{id}/failures/export', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'exportFailures'])->name('admin.documents.failures.export');
    Route::post('/admin/documents/failures/{id}/resolve', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'resolveFailure'])->name('admin.documents.failures.resolve');
    Route::get('/admin/documents/clients/search', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'searchClients'])->name('admin.documents.clients.search');
});
