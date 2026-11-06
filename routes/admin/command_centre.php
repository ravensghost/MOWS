<?php

use App\Http\Controllers\Admin\CommandCentreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CommandCentreController::class, 'index'])->name('dashboard');
