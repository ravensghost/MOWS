<?php

use App\Http\Controllers\Admin\AccessClearancePermissionController;
use App\Http\Controllers\Admin\AccessClearanceRoleController;
use Illuminate\Support\Facades\Route;

Route::get('/access-clearance-roles', [AccessClearanceRoleController::class, 'index'])->name('access_clearance_roles.index');
Route::post('/access-clearance-roles', [AccessClearanceRoleController::class, 'store'])->name('access_clearance_roles.store');
Route::delete('/access-clearance-roles/{id}', [AccessClearanceRoleController::class, 'destroy'])->name('access_clearance_roles.destroy');
Route::post('/access-clearance-roles/permissions', [AccessClearancePermissionController::class, 'store'])->name('access_clearance_roles.permissions.store');
Route::delete('/access-clearance-roles/permissions/{id}', [AccessClearancePermissionController::class, 'destroy'])->name('access_clearance_roles.permissions.destroy');
