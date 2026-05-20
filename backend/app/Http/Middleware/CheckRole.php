<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        abort_if(!$user || !$user->hasAnyRole($roles), 403, 'You do not have permission to access this resource.');

        return $next($request);
    }
}
