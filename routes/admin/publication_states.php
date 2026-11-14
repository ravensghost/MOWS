<?php

use App\Http\Controllers\Admin\PublicationController;
use Illuminate\Support\Facades\Route;

Route::post('/publication-states', [PublicationController::class, 'storeStatus'])
    ->name('admin.publication_states.store');

Route::put('/publication-states/{id}', [PublicationController::class, 'updateStatus'])
    ->name('admin.publication_states.update');

Route::delete('/publication-states/{id}', [PublicationController::class, 'destroyStatus'])
    ->name('admin.publication_states.destroy');