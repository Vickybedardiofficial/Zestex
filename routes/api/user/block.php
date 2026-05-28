<?php
/*
|--------------------------------------------------------------------------
| Zestex - The Ultimate Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav. Full-Stack Web Developer, UI/UX Designer.
|--------------------------------------------------------------------------
| Copyright (c)  Zestex. All rights reserved.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

Route::post('/block', [App\Http\Controllers\Api\User\Block\BlockController::class, 'blockUser']);
Route::post('/unblock', [App\Http\Controllers\Api\User\Block\BlockController::class, 'unblockUser']);
Route::get('/list', [App\Http\Controllers\Api\User\Block\BlockController::class, 'listBlocks']);
