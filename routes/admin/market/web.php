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

Route::get('/', [App\Http\Controllers\Admin\Market\MarketController::class, 'index'])->name('admin.market.index');

Route::middleware('sided.layout')->get('/show/{productId}', [App\Http\Controllers\Admin\Market\MarketController::class, 'show'])->name('admin.market.show');

Route::post('/delete/{productId}', [App\Http\Controllers\Admin\Market\MarketController::class, 'destroy'])->name('admin.market.destroy');

Route::post('/approve/{productId}', [App\Http\Controllers\Admin\Market\MarketController::class, 'approve'])->name('admin.market.approve');

Route::post('/reject/{productId}', [App\Http\Controllers\Admin\Market\MarketController::class, 'reject'])->name('admin.market.reject');