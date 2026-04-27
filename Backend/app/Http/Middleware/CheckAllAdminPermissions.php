<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAllAdminPermissions
{
    /**
     * Handle an incoming request.
     * Admin must have ALL specified permissions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = auth('salon_admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت نشده است.',
            ], 401);
        }

        if (!$admin->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'حساب کاربری شما غیرفعال است.',
            ], 403);
        }

        // Check if admin has all required permissions
        if (!empty($permissions) && !$admin->hasAllPermissions($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'شما تمام دسترسی‌های لازم برای این عملیات را ندارید.',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
