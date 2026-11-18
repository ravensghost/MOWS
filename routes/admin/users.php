<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role.update');
Route::put('/users/{user}/loa', [UserController::class, 'updateLoa'])->name('users.loa.update');
Route::post('/users/{user}/ping', [UserController::class, 'pingPlayer'])->name('users.ping');