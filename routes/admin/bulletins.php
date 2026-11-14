<?php

use App\Http\Controllers\Admin\BulletinController;
use Illuminate\Support\Facades\Route;

Route::get('/bulletins', [BulletinController::class, 'index'])->name('admin.bulletins.index');
Route::post('/bulletins', [BulletinController::class, 'store'])->name('admin.bulletins.store');
Route::put('/bulletins/{id}', [BulletinController::class, 'update'])->name('admin.bulletins.update');
Route::delete('/bulletins/{id}', [BulletinController::class, 'destroy'])->name('admin.bulletins.destroy');