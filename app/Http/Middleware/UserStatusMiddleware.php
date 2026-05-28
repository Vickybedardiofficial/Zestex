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
use App\Enums\User\UserStatus;
use Symfony\Component\HttpFoundation\Response;

class UserStatusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if(auth_check()) {
            if(me()->status == UserStatus::ONBOARDING) {
                return redirect()->route('user.onboarding.index', 'one');
            }

            if(me()->status == UserStatus::BLOCKED) {
                // TODO: Add in future blocked user page with message, reason and contact support button.
                abort(403);
            }

            if(me()->status == UserStatus::SUSPENDED) {
                // TODO: Add in future suspended user page with message, reason and contact support button.
                abort(403);
            }
        }

        return $next($request);
    }
}
