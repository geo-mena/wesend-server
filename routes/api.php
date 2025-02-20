<?php

use App\Http\Controllers\CronJob\CronController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\File\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::post('upload/chunk', [FileController::class, 'upload']);
    Route::post('upload/finalize', [FileController::class, 'finalize']);
    Route::post('transfer/email', [TransferController::class, 'createEmailTransfer']);
    Route::post('transfer/link', [TransferController::class, 'createLinkTransfer']);
    Route::get('download/{token}', [TransferController::class, 'download'])->name('download');
});

Route::controller(CronController::class)
    ->prefix('cron')
    ->group(function () {
        Route::get('clean-orphaned', 'cleanOrphanedFiles');
        Route::get('clean-transfers', 'cleanExpiredTransfers');
        Route::get('clean-rate-limits', 'cleanOrphanedRecords');
        Route::get('clean-direct-transfers', 'cleanDirectTransfers');
        Route::get('clean-temp-databases', 'cleanExpiredDatabases');
    });
