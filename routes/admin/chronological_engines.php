<?php

use App\Http\Controllers\Admin\ChronologicalEngineController;
use Illuminate\Support\Facades\Route;

Route::post('/time-systems', [ChronologicalEngineController::class, 'store']);
Route::put('/time-systems/{chronologicalEngine}', [ChronologicalEngineController::class, 'update']);
Route::delete('/time-systems/{chronologicalEngine}', [ChronologicalEngineController::class, 'destroy']);
