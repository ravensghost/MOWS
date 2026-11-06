<?php

use App\Http\Controllers\Admin\AssignmentController;
use Illuminate\Support\Facades\Route;

Route::post('/sections/{id}/positions', [AssignmentController::class, 'store']);
Route::put('/positions/{position}', [AssignmentController::class, 'update']);
Route::delete('/positions/{position}', [AssignmentController::class, 'destroy']);
