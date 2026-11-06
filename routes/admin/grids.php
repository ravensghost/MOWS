<?php

use App\Http\Controllers\Admin\GridController;
use Illuminate\Support\Facades\Route;

Route::post('/layouts', [GridController::class, 'store']);
Route::put('/layouts/{layout}', [GridController::class, 'update']);
Route::delete('/layouts/{layout}', [GridController::class, 'destroy']);
