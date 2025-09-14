<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GeminiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/reg', [AuthController::class, 'register']);

// Route::post('/gemini/ask', [GeminiController::class, 'askQuestion']);
