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

Route::get('/', [App\Http\Controllers\Admin\Post\PostController::class, 'index'])->name('admin.posts.index');

Route::middleware('sided.layout')->get('/show/{postId}', [App\Http\Controllers\Admin\Post\PostController::class, 'show'])->name('admin.posts.show');

Route::middleware('sided.layout')->post('/delete/{postId}', [App\Http\Controllers\Admin\Post\PostController::class, 'destroy'])->name('admin.posts.destroy');