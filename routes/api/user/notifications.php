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

Route::get('/all', [App\Http\Controllers\Api\User\Notification\NotificationController::class, 'getAll']);
Route::get('/mentions', [App\Http\Controllers\Api\User\Notification\NotificationController::class, 'getMentions']);
Route::get('/important', [App\Http\Controllers\Api\User\Notification\NotificationController::class, 'getImportant']);
Route::get('/unread/count', [App\Http\Controllers\Api\User\Notification\NotificationController::class, 'getUnreadCount']);
Route::delete('/delete', [App\Http\Controllers\Api\User\Notification\NotificationController::class, 'deleteNotification']);