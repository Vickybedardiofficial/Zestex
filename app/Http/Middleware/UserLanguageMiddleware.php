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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class UserLanguageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale');

        if(auth_check()) {
            $locale = me()->language;
        }
        
        else if (Cookie::has('selected_locale')) {
            $locale = Cookie::get('selected_locale');
        }

        else if (session()->has('selected_locale')) {
            $locale = session()->get('selected_locale');
        }

        $locale = is_string($locale) ? strtolower(trim($locale)) : config('app.locale');
        $allowedLocales = available_locales();

        if (! preg_match('/^[a-z]{2}([-_][a-z0-9]+)?$/i', $locale) || ! in_array($locale, $allowedLocales, true)) {
            $locale = config('app.locale');
            Cookie::queue(Cookie::forget('selected_locale'));
            session()->forget('selected_locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
