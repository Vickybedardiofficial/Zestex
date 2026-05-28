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

Route::delete('/profile/delete', [App\Http\Controllers\Api\Admin\AdminController::class, 'deleteProfile']);
Route::middleware(['api_key'])->post('/verification/user/verify', [App\Http\Controllers\Api\Admin\VerificationController::class, 'verifyUser']);

Route::get('/settings/ai', [App\Http\Controllers\Api\Admin\AdminSettingController::class, 'index']);
Route::post('/settings/ai', [App\Http\Controllers\Api\Admin\AdminSettingController::class, 'update']);
