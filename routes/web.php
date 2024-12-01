<?php

use App\Http\Controllers\File\FileController;
use App\Http\Controllers\File\TransferController;
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
    return view('welcome');
});

Route::prefix('v1')->group(function () {
    Route::post('upload/chunk', [FileController::class, 'upload']);
    Route::post('upload/finalize', [FileController::class, 'finalize']);
    Route::post('transfer/email', [TransferController::class, 'createEmailTransfer']);
    Route::post('transfer/link', [TransferController::class, 'createLinkTransfer']);
    Route::get('download/{token}', [TransferController::class, 'download'])->name('download');
});
