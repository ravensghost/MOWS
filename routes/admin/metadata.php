<?php

use App\Http\Controllers\Admin\MetadataController;
use Illuminate\Support\Facades\Route;

Route::get('/metadata', [MetadataController::class, 'index'])->name('metadata.index');
Route::post('/metadata', [MetadataController::class, 'storeForm'])->name('metadata.store');
Route::put('/metadata/{id}', [MetadataController::class, 'updateForm'])->name('metadata.update');
Route::delete('/metadata/{id}', [MetadataController::class, 'destroyForm'])->name('metadata.destroy');

Route::post('/metadata/fields', [MetadataController::class, 'storeField'])->name('metadata.fields.store');
Route::put('/metadata/fields/{id}', [MetadataController::class, 'updateField'])->name('metadata.fields.update');
Route::delete('/metadata/fields/{id}', [MetadataController::class, 'destroyField'])->name('metadata.fields.destroy');