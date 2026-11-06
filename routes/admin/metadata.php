<?php

use App\Http\Controllers\Admin\MetadataController;
use Illuminate\Support\Facades\Route;

Route::get('/forms', [MetadataController::class, 'index']);
Route::post('/forms', [MetadataController::class, 'storeForm']);
Route::put('/forms/{id}', [MetadataController::class, 'updateForm']);
Route::delete('/forms/{id}', [MetadataController::class, 'destroyForm']);
Route::post('/forms/fields', [MetadataController::class, 'storeField']);
Route::put('/forms/fields/{id}', [MetadataController::class, 'updateField']);
Route::delete('/forms/fields/{id}', [MetadataController::class, 'destroyField']);
