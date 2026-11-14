<?php

use App\Http\Controllers\Admin\ChronologicalRegisterController;
use App\Http\Controllers\Admin\InsigniaPromotionController;
use App\Http\Controllers\Admin\PersonaStatusController;
use App\Http\Controllers\Admin\PersonaTransferController;
use Illuminate\Support\Facades\Route;

Route::get('/chronological-registers', [ChronologicalRegisterController::class, 'index'])
    ->name('chronological_registers.index');

Route::put('/chronological-registers/{chronologicalRegister}', [ChronologicalRegisterController::class, 'updateRecord'])
    ->name('chronological_registers.update');

Route::post('/chronological-registers/manual-entry', [ChronologicalRegisterController::class, 'issueManualEntry'])
    ->name('chronological_registers.issueManualEntry');

Route::post('/chronological-registers/award', [ChronologicalRegisterController::class, 'issueAward'])
    ->name('chronological_registers.award');

Route::post('/chronological-registers/{chronologicalRegister}/remove', [ChronologicalRegisterController::class, 'removeAward'])
    ->name('chronological_registers.remove');

Route::post('/chronological-registers/promotion', InsigniaPromotionController::class)
    ->name('chronological_registers.promotion');

Route::post('/chronological-registers/transfer', PersonaTransferController::class)
    ->name('chronological_registers.issueTransfer');

Route::post('/chronological-registers/status-change', PersonaStatusController::class)
    ->name('chronological_registers.issueStatusChange');