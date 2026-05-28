<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\BusinessAccount;
use Illuminate\Http\Request;

class EnsureBusinessAccountMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth_check()) {
            $user = me();

            if ($user && ! $user->businessAccount()->exists()) {
                // Minimal defaults so the business area can render consistently.
                BusinessAccount::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'billing_address' => [],
                    'verified' => false,
                    'is_reviewed' => false,
                    'updated_at' => null,
                ]);
            }
        }

        return $next($request);
    }
}

