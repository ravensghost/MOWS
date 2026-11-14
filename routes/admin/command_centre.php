<?php

use App\Http\Controllers\Admin\CommandCentreController;
use Illuminate\Support\Facades\Route;

Route::prefix('command-centre')
    ->name('command_centre.')
    ->group(function () {
        Route::get('/', [CommandCentreController::class, 'index'])->name('index');
    });