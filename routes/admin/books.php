<?php

use App\Http\Controllers\Admin\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/books', [BookController::class, 'index'])->name('admin.books.index');
Route::get('/books/create', [BookController::class, 'create'])->name('admin.books.create');
Route::post('/books', [BookController::class, 'store'])->name('admin.books.store');
Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('admin.books.edit');
Route::put('/books/{book}', [BookController::class, 'update'])->name('admin.books.update');
Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('admin.books.destroy');