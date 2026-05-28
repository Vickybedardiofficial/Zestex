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

use App\Models\Chat;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.Chat.{chatId}', function ($user, $chatId) {
    $chatId = (Str::isUuid($chatId) ? $chatId : null);

    $chatData = Chat::where('chat_id', $chatId)->first();
    
    if($chatData) {
        return $chatData->participants()->where('user_id', $user->id)->exists();
    }

    return false;
});
