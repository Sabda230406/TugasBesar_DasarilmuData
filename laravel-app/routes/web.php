<?php

use App\Http\Controllers\AdminController;
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
    Route::get('/history', [PredictionController::class, 'history'])->name('history');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::get('/admin/retraining', [AdminController::class, 'retraining'])->name('admin.retraining');
        Route::post('/admin/retraining/start', [AdminController::class, 'startRetraining'])->name('admin.retraining.start');
        Route::get('/admin/retraining/runs/status', [AdminController::class, 'retrainingRunStatus'])->name('admin.retraining.runs.status');
        Route::get('/admin/retraining/runs/{run}/status', [AdminController::class, 'retrainingRunStatus'])->name('admin.retraining.runs.show');
        Route::post('/admin/retraining/history/import', [AdminController::class, 'importHistoryToRetraining'])->name('admin.retraining.history.import');
        Route::post('/admin/retraining/reset-lock', [AdminController::class, 'resetRetrainingLock'])->name('admin.retraining.reset-lock');
        Route::post('/admin/retraining/datasets/{dataset}/archive', [AdminController::class, 'archiveDataset'])->name('admin.retraining.archive');
        Route::post('/admin/retraining/datasets/{dataset}/restore', [AdminController::class, 'restoreDataset'])->name('admin.retraining.restore');
        Route::get('/admin/history/export', [AdminController::class, 'exportHistory'])->name('admin.history.export');
        Route::get('/admin/models', [AdminController::class, 'models'])->name('admin.models');
        Route::post('/admin/models/versions/{version}/activate', [AdminController::class, 'activateModelVersion'])->name('admin.models.versions.activate');
        Route::post('/admin/models/runs/{run}/activate', [AdminController::class, 'activateRetrainingRun'])->name('admin.models.runs.activate');
        Route::post('/admin/models/runs/{run}/archive', [AdminController::class, 'archiveRetrainingRun'])->name('admin.models.runs.archive');
        Route::post('/admin/models/runs/archive-inactive', [AdminController::class, 'archiveInactiveRetrainingRuns'])->name('admin.models.runs.archive-inactive');

        Route::get('/retraining', fn () => redirect()->route('admin.retraining'))->name('retraining');
        Route::post('/retraining/upload', [RetrainingController::class, 'upload'])->name('retraining.upload');
        Route::post('/retraining/manual', [RetrainingController::class, 'manual'])->name('retraining.manual');
        Route::post('/retraining/datasets/{dataset}/archive', [RetrainingController::class, 'archive'])->name('retraining.archive');
        Route::post('/retraining/start', [AdminController::class, 'startRetraining'])->name('retraining.start');
    });
});
