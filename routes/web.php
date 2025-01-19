<?php

use App\Http\Controllers\File\FileController;
use App\Http\Controllers\File\TransferController;
use App\Http\Controllers\P2P\P2PController;
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

Route::get('/', function () {
    return view('home.welcome');
});

Route::prefix('v1')->group(function () {
    Route::post('upload/chunk', [FileController::class, 'upload']);
    Route::post('upload/finalize', [FileController::class, 'finalize']);
    Route::post('transfer/email', [TransferController::class, 'createEmailTransfer']);
    Route::post('transfer/link', [TransferController::class, 'createLinkTransfer']);
    Route::get('download/{token}', [TransferController::class, 'download'])->name('download');

    Route::get('transfer/check/{token}', [TransferController::class, 'checkTransfer']);
    Route::post('transfer/validate/{token}', [TransferController::class, 'validatePassword']);

    Route::delete('upload/chunks/{uploadId}', [FileController::class, 'deleteChunks']);
    Route::delete('files/{id}', [FileController::class, 'deleteFile']);

    Route::post('upload/finalize-batch', [FileController::class, 'finalizeBatch']);

    //! Preview file PDF
    Route::get('/transfer/{token}/preview', [TransferController::class, 'previewFile'])->name('transfer.preview');

    //! Check if an IP can upload more files
    Route::post('upload/check-limit', [FileController::class, 'checkLimit']);
});

Route::prefix('p2p')->group(function () {
    Route::post('/session', [P2PController::class, 'createSession']);
    Route::post('/answer', [P2PController::class, 'answerOffer']);
    Route::post('/ice', [P2PController::class, 'exchangeICE']);
});