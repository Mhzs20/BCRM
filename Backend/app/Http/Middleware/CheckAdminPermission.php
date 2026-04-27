<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    /**
     * Handle an incoming request.
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

        // Check if admin has any of the required permissions
        if (!empty($permissions) && !$admin->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی لازم برای این عملیات را ندارید.',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
