<?php

use App\Http\Controllers\Admin\GridController;
use Illuminate\Support\Facades\Route;

Route::post('/grids', [GridController::class, 'store'])->name('admin.grids.store');
Route::put('/grids/{grid}', [GridController::class, 'update'])->name('admin.grids.update');
Route::delete('/grids/{grid}', [GridController::class, 'destroy'])->name('admin.grids.destroy');