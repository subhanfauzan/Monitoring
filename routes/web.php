<?php

use App\Http\Controllers\Api\DapotApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DapotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GeminiChatController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\NopController;
use App\Http\Controllers\TiketController;
use App\Http\Controllers\TiketIssueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.login');
})->name('pages.login');
Route::get('/login', function () {
    return view('pages.login');
})->name('login.view');
Route::get('/register', function () {
    return view('pages.register');
});
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('utama');
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('utama');
    });

    Route::get('/nop', [NopController::class, 'index'])->name('nop.index');
    Route::post('/nop/store', [NopController::class, 'store'])->name('nop.store');
    Route::put('/nop/{id}', [NopController::class, 'update'])->name('nop.update');
    Route::delete('/nop/{id}', [NopController::class, 'destroy'])->name('nop.destroy');

    Route::get('/dapot', [DapotController::class, 'index'])->name('dapot.index');
    Route::post('/dapot/store', [DapotController::class, 'store'])->name('dapot.store');
    Route::put('/dapot/{id}', [DapotController::class, 'update'])->name('dapot.update');
    Route::delete('/dapot/{id}', [DapotController::class, 'destroy'])->name('dapot.destroy');
    Route::post('dapot-import', [DapotController::class, 'import'])->name('dapot.import');

    Route::controller(TiketController::class)->group(function () {
        Route::get('/tiket', 'index')->name('tiket.index');
        Route::get('/tiket/count', 'getCount')->name('tiket.count');
        Route::post('tiket-import', 'import')->name('tiket.import');
        Route::get('/export/{id}', 'export')->name('tiket.export');
        Route::post('/tiket/store', 'store')->name('tiket.store');
        Route::put('/tiket/{id}', 'update')->name('tiket.update');
        Route::delete('/tiket/deleteall', 'destroyall')->name('tiket.destroyall');
        Route::delete('/tiket/{id}', 'destroy')->name('tiket.destroy');
        Route::delete('/daftartiketnop/{id}', 'destroynop')->name('tiket.destroynop');
        Route::get('/autocomplete', 'autocomplete')->name('autocomplete');
        Route::post('/tiket/bulk-update', 'bulkUpdate')->name('tiket.bulk-update');
        Route::put('/tiket/lock/{id}', 'toggleLock')->name('tiket.toggleLock');
        // Route::post('/ask', 'askQuestion')->name('tiket.askQuestion');
    });

    Route::get('/tiketissue/{id}', [TiketIssueController::class, 'show'])->name('tiketissue.show');

    Route::controller(DapotApiController::class)
        ->prefix('/api/dapot')
        ->group(function () {
            Route::get('/searchBySiteID', 'searchBySiteId')->name('api.dapot.searchBySiteID');
            Route::get('/findBySiteID', 'findBySiteId')->name('api.dapot.findBySiteID');
        });

    Route::get('/ppp', function () {
        return view('pages.result');
    })->name('export.view');

    Route::get('/chat', function () {
        return view('pages.chat');
    });

    Route::post('/chat/ask', [ChatController::class, 'askQuestion'])->name('chat.ask');
    Route::post('/gemini/ask', [GeminiChatController::class, 'askQuestion'])->name('gemini.ask');
});
