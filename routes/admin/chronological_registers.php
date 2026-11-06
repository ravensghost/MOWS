<?php

use App\Http\Controllers\Admin\ChronologicalRegisterController;
use App\Http\Controllers\Admin\InsigniaPromotionController;
use App\Http\Controllers\Admin\PersonaTransferController;
use App\Http\Controllers\Admin\PersonaStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/service-records', [ChronologicalRegisterController::class, 'index'])->name('service-records.index');
Route::put('/service-records/{serviceRecord}', [ChronologicalRegisterController::class, 'updateRecord'])->name('service-records.update');
Route::post('/service-records/manual-entry', [ChronologicalRegisterController::class, 'issueManualEntry'])->name('service-records.issueManualEntry');
Route::post('/service-records/award', [ChronologicalRegisterController::class, 'issueAward'])->name('service-records.award');
Route::post('/service-records/{serviceRecord}/remove', [ChronologicalRegisterController::class, 'removeAward'])->name('service-records.remove');

Route::post('/service-records/promotion', InsigniaPromotionController::class)->name('service-records.promotion');
Route::post('/service-records/transfer', PersonaTransferController::class)->name('service-records.issueTransfer');
Route::post('/service-records/status-change', PersonaStatusController::class)->name('service-records.issueStatusChange');
