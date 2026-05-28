<?php

use Illuminate\Support\Facades\Route;

Route::get('/general', [App\Http\Controllers\Admin\Config\ConfigController::class, 'general'])->name('admin.config.general');

Route::get('/email', [App\Http\Controllers\Admin\Config\ConfigController::class, 'email'])->name('admin.config.email');

Route::post('/email/testing', [App\Http\Controllers\Admin\Config\ConfigController::class, 'emailTesting'])->name('admin.config.email-testing');

Route::get('/notifications', [App\Http\Controllers\Admin\Config\ConfigController::class, 'notifications'])->name('admin.config.notifications');

Route::get('/api', [App\Http\Controllers\Admin\Config\ConfigController::class, 'api'])->name('admin.config.api');

Route::get('/verification', [App\Http\Controllers\Admin\Config\ConfigController::class, 'verification'])->name('admin.config.verification');
Route::post('/verification', [App\Http\Controllers\Admin\Config\ConfigController::class, 'updateVerification'])->name('admin.config.verification.update');

// AI Settings
Route::get('/ai', [App\Http\Controllers\Admin\Config\AiConfigController::class, 'index'])->name('admin.config.ai');
Route::post('/ai', [App\Http\Controllers\Admin\Config\AiConfigController::class, 'update'])->name('admin.config.ai.update');