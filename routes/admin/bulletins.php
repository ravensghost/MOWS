<?php

use App\Http\Controllers\Admin\BulletinController;
use Illuminate\Support\Facades\Route;

Route::get('/bulletins', [BulletinController::class, 'index'])->name('bulletins.index');
Route::post('/bulletins', [BulletinController::class, 'store'])->name('bulletins.store');
Route::put('/bulletins/{id}', [BulletinController::class, 'update'])->name('bulletins.update');
Route::delete('/bulletins/{id}', [BulletinController::class, 'destroy'])->name('bulletins.destroy');