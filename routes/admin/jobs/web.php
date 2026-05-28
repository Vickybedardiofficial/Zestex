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

Route::get('/', [App\Http\Controllers\Admin\Job\JobController::class, 'index'])->name('admin.jobs.index');

Route::middleware('sided.layout')->get('/show/{jobId}', [App\Http\Controllers\Admin\Job\JobController::class, 'show'])
	->name('admin.jobs.show');

Route::post('/destroy/{jobId}', [App\Http\Controllers\Admin\Job\JobController::class, 'destroy'])
	->name('admin.jobs.destroy');

Route::post('/approve/{jobId}', [App\Http\Controllers\Admin\Job\JobController::class, 'approve'])
	->name('admin.jobs.approve');

Route::post('/reject/{jobId}', [App\Http\Controllers\Admin\Job\JobController::class, 'reject'])
	->name('admin.jobs.reject');