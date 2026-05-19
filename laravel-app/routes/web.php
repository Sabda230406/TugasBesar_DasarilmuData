<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PredictionController::class, 'landing']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/form', [PredictionController::class, 'index'])->name('form');
    Route::post('/predict', [PredictionController::class, 'predict'])->name('predict');
    Route::get('/history', [PredictionController::class, 'history'])->name('history');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
