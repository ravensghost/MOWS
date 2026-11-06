<?php

use App\Http\Controllers\Admin\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [ConfigurationController::class, 'index']);
Route::post('/settings/update', [ConfigurationController::class, 'update']);
