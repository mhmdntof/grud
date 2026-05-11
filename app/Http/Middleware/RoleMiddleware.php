<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
   public function handle($request, Closure $next, $roleName)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    if ($user->role->name !== $roleName) {
        return response()->json([
            'message' => 'Forbidden'
        ], 403);
    }

    return $next($request);
}
}