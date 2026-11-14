<?php

use App\Http\Controllers\Admin\ShelfController;
use Illuminate\Support\Facades\Route;

Route::post('/shelves/reorder', [ShelfController::class, 'reorder'])->name('admin.shelves.reorder');
Route::post('/shelves', [ShelfController::class, 'store'])->name('admin.shelves.store');
Route::put('/shelves/{id}', [ShelfController::class, 'update'])->name('admin.shelves.update');
Route::delete('/shelves/{id}', [ShelfController::class, 'destroy'])->name('admin.shelves.destroy');