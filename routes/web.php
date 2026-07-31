<?php

use App\Helpers\ExportHelper;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterClient\ClientController;
use App\Models\Client\Client;
use App\Models\Gensen\GensenExportImportHistory;
use App\Models\User;
use App\Repositories\Client\ClientRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

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

Route::post('/lw-test', function (\Illuminate\Http\Request $request) {
    logger()->info('LW TEST', [
        'headers' => $request->headers->all(),
        'cookies' => $request->cookies->all(),
    ]);

    return response()->json(['ok' => true]);
});

// Route::get('/dev-login', function () {
//     $user = \App\Models\User::first();
//     auth()->login($user);
//     return redirect('/admin/documents');
// });


// Route::get('/generate-ai', function () {
//     return User::query()
//         ->whereHas('client', function ($query) {
//             $query->where('pic', 'ainul')
//                 ->doesntHave('magicLink');
//         })
//         ->get();
//     $c =
//         Client::where('pic', 'ainul')
//         ->where('source_file_name', '8B 2607')
//         ->whereHas('user', function ($query) {
//             $query->where(
//                 'created_at',
//                 '<',
//                 Carbon::parse('2026-07-18 12
//                 :00:00')
//             );
//         });

//     return $c->get()->toArray();
//     // return $c->get()->toArray();

//     $fileName = "Data Client AI";
//     return ExportHelper::export(
//         ExportHelper::TYPE_EXCEL,
//         $fileName,
//         $c,
//         'app.master-client.export-ainul',
//         [
//             'title' => 'Data Client',
//             'type' => ExportHelper::TYPE_EXCEL,
//         ],
//         [
//             'size' => 'legal',
//             'orientation' => 'landscape',
//         ]
//     );
// });

Route::middleware(['auth', 'access_permission', 'esign_required'])->group(function () {
    Route::group(["controller" => DashboardController::class, "prefix" => "dashboard", "as" => "dashboard."], function () {
        Route::get('/', 'index')->name('index');
    });
    Route::group(["controller" => DashboardController::class, "prefix" => "client/dashboard", "as" => "dashboard."], function () {
        Route::get('/', 'index')->name('index');
    });
});

// Client Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/documents/{type}/download', [\App\Http\Controllers\Client\DocumentController::class, 'download'])->name('documents.download');
    Route::get('/agreement/download', [ClientController::class, 'download'])->name('agreement.download');
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
    Route::get('/admin/documents/{client}/download-zip', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'downloadZip'])->name('admin.documents.download-zip');
    Route::get('/admin/documents/batches/{batch}/download-status/export', [\App\Http\Controllers\Admin\AdminDocumentController::class, 'exportDownloadStatus'])->name('admin.documents.download-status.export');
});
