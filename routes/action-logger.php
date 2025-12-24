<?php

declare(strict_types=1);

use Enadstack\ActionLogger\Http\Controllers\ActionLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Action Logger UI Routes
|--------------------------------------------------------------------------
|
| These routes are only registered when config('action-logger.ui.enabled') is true.
| They provide the Inertia-based UI for viewing action logs.
|
*/

Route::get('/', [ActionLogController::class, 'dashboard'])
    ->name('action-logger.dashboard');

Route::get('/all', [ActionLogController::class, 'all'])
    ->name('action-logger.all');

Route::get('/modules/{moduleKey}', [ActionLogController::class, 'module'])
    ->name('action-logger.modules.show');

Route::get('/subject/{subjectType}/{subjectId}', [ActionLogController::class, 'subject'])
    ->name('action-logger.subject.show');
