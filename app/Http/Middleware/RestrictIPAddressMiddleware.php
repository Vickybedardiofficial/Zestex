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
use App\Services\Blacklist\BlacklistService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictIPAddressMiddleware
{
    private BlacklistService $blacklistService;

    public function __construct(BlacklistService $blacklistService) {
        $this->blacklistService = $blacklistService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->ip(), $this->getBlockIps())) {
            abort(403, __('auth.ip_blocked'));
        }

        return $next($request);
    }

    private function getBlockIps(): array
    {
        return $this->blacklistService->getBlacklistedIps();
    }
}
