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
use App\Data\DataCapsule;
use App\Jobs\User\Views\RegisterResourceViews;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TerminatingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request): void
    {
        $dataCapsule = app()->make(DataCapsule::class);
        $capsuledViews = $dataCapsule->get('resourceViews');

        if(! empty($capsuledViews)) {
            RegisterResourceViews::dispatch($capsuledViews);
        }
    }
}
