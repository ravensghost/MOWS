<?php

use App\Http\Controllers\Admin\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/pages', [BookController::class, 'index']);
Route::post('/pages', [BookController::class, 'store']);
Route::put('/pages/{page}', [BookController::class, 'update']);
Route::delete('/pages/{page}', [BookController::class, 'destroy']);
Route::get('/pages/create', [BookController::class, 'create']);
Route::get('/pages/{page}/edit', [BookController::class, 'edit']);
