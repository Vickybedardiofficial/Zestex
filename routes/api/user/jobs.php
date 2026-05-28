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

Route::get('/categories', [App\Http\Controllers\Api\User\Job\JobController::class, 'getCategories']);
Route::post('/jobs', [App\Http\Controllers\Api\User\Job\JobController::class, 'getJobs']);
Route::get('/jobs/{jobId}', [App\Http\Controllers\Api\User\Job\JobController::class, 'getJobData']);
Route::post('/bookmarks/add', [App\Http\Controllers\Api\User\Job\JobController::class, 'bookmark']);
