<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\RetrainingController;
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
    Route::get('/upload', [PredictionController::class, 'upload'])->name('upload');
    Route::post('/upload/predict', [PredictionController::class, 'predictUpload'])->name('upload.predict');
    Route::get('/retraining', [RetrainingController::class, 'index'])->name('retraining');
    Route::post('/retraining/upload', [RetrainingController::class, 'upload'])->name('retraining.upload');
    Route::post('/retraining/manual', [RetrainingController::class, 'manual'])->name('retraining.manual');
    Route::post('/retraining/datasets/{dataset}/archive', [RetrainingController::class, 'archive'])->name('retraining.archive');
    Route::post('/retraining/start', [RetrainingController::class, 'start'])->name('retraining.start');
    Route::get('/history', [PredictionController::class, 'history'])->name('history');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
