<?php

use App\Http\Controllers\Admin\PublicationController;
use Illuminate\Support\Facades\Route;

Route::post('/publication-states', [PublicationController::class, 'storeStatus'])
    ->name('publication_states.store');

Route::put('/publication-states/{id}', [PublicationController::class, 'updateStatus'])
    ->name('publication_states.update');

Route::delete('/publication-states/{id}', [PublicationController::class, 'destroyStatus'])
    ->name('publication_states.destroy');