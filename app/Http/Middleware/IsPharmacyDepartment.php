<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsPharmacyDepartment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. التحقق من أن المستخدم مسجل دخول
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        // 2. تحميل العلاقات بشكل صريح (مهم جداً!)
        $user->load(['role', 'department']);

        // 3. التحقق من أن المستخدم رئيس قسم
        if (!$user->role || $user->role->name !== 'department_head') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الميزة متاحة لرؤساء الأقسام فقط'
            ], 403);
        }

        // 4. التحقق من أن القسم هو الصيدلية
        $pharmacyNames = ['صيدلية', 'pharmacy'];

        if (!$user->department ||
            !in_array(
                strtolower(trim($user->department->name)),
                array_map('strtolower', $pharmacyNames)
            )) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الميزة متاحة لقسم الصيدلية فقط'
            ], 403);
        }

        return $next($request);
    }
}
