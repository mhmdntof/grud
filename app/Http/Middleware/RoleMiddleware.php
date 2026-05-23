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

    // 🏆 التعديل الاحترافي: إجبار لارافيل على جلب علاقة الـ role بشكل صحيح وآمن
    // لمنع أي مشاكل ناتجة عن اختلاف الـ Guard
    if (!$user->relationLoaded('role')) {
        $user->load('role');
    }

    // التحقق الآمن من الاسم
    if (!$user->role || $user->role->name !== $roleName) {
        return response()->json([
            'message' => 'Forbidden'
        ], 403);
    }

    return $next($request);
}
}