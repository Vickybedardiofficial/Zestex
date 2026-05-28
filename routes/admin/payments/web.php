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

Route::get('/', [App\Http\Controllers\Admin\Payment\PaymentController::class, 'index'])->name('admin.payments.index');

Route::get('/show/{paymentId}', [App\Http\Controllers\Admin\Payment\PaymentController::class, 'show'])->name('admin.payments.show');