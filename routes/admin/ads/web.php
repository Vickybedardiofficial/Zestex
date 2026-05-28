<?php
/*
|--------------------------------------------------------------------------
| Zestex - The Ultimate Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav. Full-Stack Web Developer, UI/UX Designer.
| Website: 
| E-mail: vicktbedardi9@gmail.com
| Instagram: 
| Telegram: 
|--------------------------------------------------------------------------
| Copyright (c)  Zestex. All rights reserved.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\Admin\Ad\AdController::class, 'index'])->name('admin.ads.index');

Route::middleware('sided.layout')->get('/show/{adId}', [App\Http\Controllers\Admin\Ad\AdController::class, 'show'])->name('admin.ads.show');
Route::post('/destroy/{adId}', [App\Http\Controllers\Admin\Ad\AdController::class, 'destroy'])->name('admin.ads.destroy');
Route::post('/approve/{adId}', [App\Http\Controllers\Admin\Ad\AdController::class, 'approve'])->name('admin.ads.approve');
Route::post('/reject/{adId}', [App\Http\Controllers\Admin\Ad\AdController::class, 'reject'])->name('admin.ads.reject');