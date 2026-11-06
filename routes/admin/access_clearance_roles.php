<?php
use App\Http\Controllers\Admin\AccessClearanceRoleController;
use App\Http\Controllers\Admin\AccessClearancePermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/roles', [AccessClearanceRoleController::class, 'index'])->name('roles.index');
Route::post('/roles', [AccessClearanceRoleController::class, 'store'])->name('roles.store');
Route::delete('/roles/{id}', [AccessClearanceRoleController::class, 'destroy'])->name('roles.destroy');
Route::post('/roles/permissions', [AccessClearancePermissionController::class, 'store'])->name('permissions.store');
Route::delete('/roles/permissions/{id}', [AccessClearancePermissionController::class, 'destroy'])->name('permissions.destroy');