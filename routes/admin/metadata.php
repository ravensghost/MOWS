<?php

use App\Http\Controllers\Admin\MetadataController;
use Illuminate\Support\Facades\Route;

Route::get('/metadata', [MetadataController::class, 'index'])->name('admin.metadata.index');
Route::post('/metadata', [MetadataController::class, 'storeForm'])->name('admin.metadata.store');
Route::put('/metadata/{id}', [MetadataController::class, 'updateForm'])->name('admin.metadata.update');
Route::delete('/metadata/{id}', [MetadataController::class, 'destroyForm'])->name('admin.metadata.destroy');

Route::post('/metadata/fields', [MetadataController::class, 'storeField'])->name('admin.metadata.fields.store');
Route::put('/metadata/fields/{id}', [MetadataController::class, 'updateField'])->name('admin.metadata.fields.update');
Route::delete('/metadata/fields/{id}', [MetadataController::class, 'destroyField'])->name('admin.metadata.fields.destroy');