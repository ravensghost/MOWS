<?php

use App\Http\Controllers\Admin\ShelfController;
use Illuminate\Support\Facades\Route;

Route::post('/bookcases/reorder', [ShelfController::class, 'reorder']);
Route::post('/bookcases', [ShelfController::class, 'store']);
Route::put('/bookcases/{id}', [ShelfController::class, 'update']);
Route::delete('/bookcases/{id}', [ShelfController::class, 'destroy']);
