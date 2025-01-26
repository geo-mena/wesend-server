<?php

use App\Http\Controllers\File\EmailController;
use App\Http\Controllers\File\FileController;
use App\Http\Controllers\File\QR\DirectTransferController;
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

// FILE ROUTES
Route::controller(FileController::class)
    ->prefix('v1')
    ->middleware('auth.route')
    ->group(function () {
        Route::post('upload/chunk', 'upload');
        Route::post('upload/finalize', 'finalize');
        Route::delete('upload/chunks/{uploadId}', 'deleteChunks');
        Route::delete('files/{id}', 'deleteFile');
        Route::post('upload/finalize-batch', 'finalizeBatch');
        Route::post('upload/check-limit', 'checkLimit');
    });

// TRANSFER ROUTES
Route::controller(TransferController::class)
    ->prefix('v1')
    ->middleware('auth.route')
    ->group(function () {
        Route::post('transfer/email', 'createEmailTransfer');
        Route::post('transfer/link', 'createLinkTransfer');
        Route::get('transfer/check/{token}', 'checkTransfer');
        Route::post('transfer/validate/{token}', 'validatePassword');
        Route::get('/transfer/{token}/preview', 'previewFile')->name('transfer.preview');
    });

// DOWNLOAD & DELETE FILE
Route::controller(TransferController::class)
    ->prefix('d')
    ->middleware('auth.route')
    ->group(function () {
        Route::get('/{token}', 'download')->name('download');
        Route::delete('/transfer/{token}', 'deleteTransfer')->name('delete');
    });

// DOWNLOAD DIRECT FILE
Route::controller(DirectTransferController::class)
    ->prefix('direct')
    ->middleware('auth.route')
    ->group(function () {
        Route::post('/generate', 'generate');
        Route::post('/{token}/validate', 'validatePin');
        Route::get('/{token}/download', 'download')->name('direct.download');
        Route::get('/{token}', 'findTransfer');
    });

// EMAIL VERIFICATION
Route::controller(EmailController::class)
    ->prefix('email')
    ->middleware('auth.route')
    ->group(function () {
        Route::post('/request', 'requestVerification');
        Route::post('/verify', 'verifyCode');
        Route::get('/{email}', 'checkVerification');
    });

//! BUILDING...
Route::controller(P2PController::class)
    ->prefix('p2p')
    ->middleware('auth.route')
    ->group(function () {
        Route::post('/session', 'createSession');
        Route::post('/answer', 'answerOffer');
        Route::post('/ice', 'exchangeICE');
    });
