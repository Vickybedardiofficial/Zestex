<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Actions\User\UpdateUserDeviceAction;
use Symfony\Component\HttpFoundation\Response;

class UserOnlineMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth_check()) {
            // TODO: Replace with Redis cache storage.
            
            $user = me();

            if ($user->last_active < now()->subMinutes(config('user.online_interval_in_minutes'))) {
                $user->last_active = now();
                $user->save();

                (new UpdateUserDeviceAction())->execute($user);
            }
        }

        return $next($request);
    }
}
