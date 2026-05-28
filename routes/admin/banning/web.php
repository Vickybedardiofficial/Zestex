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

Route::get('/', [App\Http\Controllers\Admin\Banning\BanningController::class, 'index'])->name('admin.banning.index');

Route::middleware(['sided.layout'])->get('/show/{banId}', [App\Http\Controllers\Admin\Banning\BanningController::class, 'show'])->name('admin.banning.show');

Route::post('/delete/{banId}', [App\Http\Controllers\Admin\Banning\BanningController::class, 'destroy'])->name('admin.banning.delete');