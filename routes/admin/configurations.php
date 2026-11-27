<?php

use App\Http\Controllers\Admin\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::get('/configurations', [ConfigurationController::class, 'index'])->name('configurations.index');
Route::post('/configurations/update', [ConfigurationController::class, 'update'])->name('configurations.update');