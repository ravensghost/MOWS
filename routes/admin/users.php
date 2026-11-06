<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index']);
Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
Route::put('/users/{user}/loa', [UserController::class, 'updateLoa']);
Route::post('/users/{user}/ping', [UserController::class, 'pingPlayer'])->name('users.ping');
