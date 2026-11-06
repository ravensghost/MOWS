<?php

use App\Http\Controllers\Admin\VolumeController;
use App\Http\Controllers\Admin\VolumeReviewController;
use App\Http\Controllers\Admin\PublicationController;
use Illuminate\Support\Facades\Route;

Route::get('/story', [PublicationController::class, 'storyManager']);
Route::post('/books/reorder', [VolumeController::class, 'reorder']);
Route::post('/books', [VolumeController::class, 'store']);
Route::put('/books/{id}', [VolumeController::class, 'update']);
Route::delete('/books/{id}', [VolumeController::class, 'destroy']);

Route::get('/books/{book}/review', [VolumeReviewController::class, 'showModal'])->name('books.review');
Route::post('/books/{book}/review', [VolumeReviewController::class, 'store'])->name('books.review.store');
