<?php

use App\Http\Controllers\Admin\TableOfContentsController;
use Illuminate\Support\Facades\Route;

Route::get('/table-of-contents', [TableOfContentsController::class, 'index'])->name('table_of_contents.index');
Route::post('/table-of-contents', [TableOfContentsController::class, 'store'])->name('table_of_contents.store');
Route::put('/table-of-contents/{link}', [TableOfContentsController::class, 'update'])->name('table_of_contents.update');
Route::delete('/table-of-contents/{link}', [TableOfContentsController::class, 'destroy'])->name('table_of_contents.destroy');

Route::get('/table-of-contents/options', [TableOfContentsController::class, 'optionsIndex'])->name('table_of_contents.options');
Route::post('/table-of-contents/options', [TableOfContentsController::class, 'storeOption'])->name('table_of_contents.options.store');
Route::put('/table-of-contents/options/{option}', [TableOfContentsController::class, 'updateOption'])->name('table_of_contents.options.update');
Route::delete('/table-of-contents/options/{option}', [TableOfContentsController::class, 'destroyOption'])->name('table_of_contents.options.destroy');