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

Route::post('/mentions', [App\Http\Controllers\Api\User\Search\AutocompleteController::class, 'searchMentions']);
Route::post('/global', [App\Http\Controllers\Api\User\Search\AutocompleteController::class, 'searchGlobal']);
Route::post('/search', [App\Http\Controllers\Api\User\Search\AutocompleteController::class, 'searchPage']);
