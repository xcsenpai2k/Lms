<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RoleController;

/**
 * Users Role Route
 */

Route::prefix('roles')->group(function () {
    //Resource Route
    Route::get('', [RoleController::class, 'index'])
        ->name('roles.index')
        ->middleware('myweb.auth:roles.view');

    Route::get('/data', [RoleController::class, 'getRolesData'])
        ->name('roles.data')
        ->middleware('myweb.auth:roles.view');

    Route::get('/create', [RoleController::class, 'create'])
        ->name('roles.create')
        ->middleware('myweb.auth:roles.create');

    Route::post('', [RoleController::class, 'store'])
        ->name('roles.store')
        ->middleware('myweb.auth:roles.create');

    Route::get('/{role}', [RoleController::class, 'show'])
        ->name('roles.show')
        ->middleware('myweb.auth:roles.view');

    Route::get('/{role}/edit', [RoleController::class, 'edit'])
        ->name('roles.edit')
        ->middleware('myweb.auth:roles.update');

    Route::put('/{role}', [RoleController::class, 'update'])
        ->name('roles.update')
        ->middleware('myweb.auth:roles.update');

    Route::get('/{role}/duplicate', [RoleController::class, 'duplicate'])
        ->name('roles.duplicate')
        ->middleware('myweb.auth:roles.create');

    Route::delete('/delete', [RoleController::class, 'destroy'])
        ->name('roles.destroy')
        ->middleware('myweb.auth:roles.delete');
});
