<?php

use App\Http\Controllers\Admin\PublicationController;
use Illuminate\Support\Facades\Route;

Route::post('/statuses', [PublicationController::class, 'storeStatus']);
Route::put('/statuses/{id}', [PublicationController::class, 'updateStatus']);
Route::delete('/statuses/{id}', [PublicationController::class, 'destroyStatus']);
