<?php

use App\Http\Controllers\Admin\BulletinController;
use Illuminate\Support\Facades\Route;

Route::get('/news', [BulletinController::class, 'index']);
Route::post('/news', [BulletinController::class, 'store']);
Route::put('/news/{id}', [BulletinController::class, 'update']);
Route::delete('/news/{id}', [BulletinController::class, 'destroy']);
