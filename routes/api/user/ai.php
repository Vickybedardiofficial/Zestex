<?php

use Illuminate\Support\Facades\Route;

Route::post('/assistant/chat', [App\Http\Controllers\Api\User\AI\AssistantController::class, 'chat']);

