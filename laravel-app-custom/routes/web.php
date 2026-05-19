<?php

use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PredictionController::class, 'index']);
Route::post('/predict', [PredictionController::class, 'predict']);
Route::get('/history', [PredictionController::class, 'history']);
