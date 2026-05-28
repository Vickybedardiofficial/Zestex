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

Route::get('/', [App\Http\Controllers\Admin\Category\CategoryController::class, 'index'])->name('admin.categories.index');
Route::get('/create', [App\Http\Controllers\Admin\Category\CategoryController::class, 'create'])->name('admin.categories.create');
Route::get('/edit/{categoryId}', [App\Http\Controllers\Admin\Category\CategoryController::class, 'edit'])->name('admin.categories.edit');
Route::post('/upsert', [App\Http\Controllers\Admin\Category\CategoryController::class, 'upsert'])->name('admin.categories.upsert');
Route::post('/destroy/{categoryId}', [App\Http\Controllers\Admin\Category\CategoryController::class, 'destroy'])->name('admin.categories.destroy');
Route::get('/{categoryId}/show', [App\Http\Controllers\Admin\Category\CategoryController::class, 'show'])->name('admin.categories.show');