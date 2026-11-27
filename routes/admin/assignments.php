<?php

use App\Http\Controllers\Admin\AssignmentController;
use Illuminate\Support\Facades\Route;

Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
Route::post('/assignments/divisions/{id}', [AssignmentController::class, 'store'])->name('assignments.divisions.store');
Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');