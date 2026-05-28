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

Route::get('/', [App\Http\Controllers\Admin\Currency\CurrencyController::class, 'index'])->name('admin.currency.index');

Route::get('/show/{currencyId}', [App\Http\Controllers\Admin\Currency\CurrencyController::class, 'show'])->name('admin.currency.show');