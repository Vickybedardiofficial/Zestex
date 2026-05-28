<?php

use Illuminate\Support\Facades\Route;

Route::prefix('accounts')->group(function () {
	Route::get('/', [App\Http\Controllers\Admin\Business\BusinessAccountController::class, 'index'])
		->name('admin.business.accounts.index');

	Route::post('/{accountId}/approve', [App\Http\Controllers\Admin\Business\BusinessAccountController::class, 'approve'])
		->name('admin.business.accounts.approve');

	Route::post('/{accountId}/reject', [App\Http\Controllers\Admin\Business\BusinessAccountController::class, 'reject'])
		->name('admin.business.accounts.reject');
});

