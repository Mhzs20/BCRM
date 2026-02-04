<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendOtpRequest;
use App\Http\Requests\Admin\VerifyOtpRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\OtpService;
use App\Models\SalonAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP for registration.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->sendOtp(
            $request->mobile,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 429);
    }

    /**
     * Verify OTP and get temp token.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->verifyOtp(
            $request->mobile,
            $request->otp_code
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Login with mobile and password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Find admin by mobile
        $admin = SalonAdmin::where('mobile', $request->mobile)
            ->active()
            ->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل یا رمز عبور اشتباه است.',
            ], 401);
        }

        // Check if salon is active (if you have salon status)
        if (!$admin->salon->is_active ?? false) {
            return response()->json([
                'success' => false,
                'message' => 'حساب سالن غیرفعال است.',
            ], 403);
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($admin);

        // Update last login
        $admin->updateLastLogin();

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد.',
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // in seconds
                'admin' => [
                    'id' => $admin->id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'mobile' => $admin->mobile,
                    'email' => $admin->email,
                    'salon_id' => $admin->salon_id,
                    'permissions' => $admin->permissions()->pluck('name'),
                ],
            ],
        ]);
    }

    /**
     * Get authenticated admin info.
     */
    public function me(): JsonResponse
    {
        $admin = auth('salon_admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت نشده است.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'full_name' => $admin->full_name,
                'mobile' => $admin->mobile,
                'email' => $admin->email,
                'salon_id' => $admin->salon_id,
                'is_active' => $admin->is_active,
                'last_login_at' => $admin->last_login_at,
                'permissions' => $admin->permissions()->get(['id', 'name', 'display_name', 'category']),
            ],
        ]);
    }

    /**
     * Logout admin.
     */
    public function logout(): JsonResponse
    {
        auth('salon_admin')->logout();

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد.',
        ]);
    }

    /**
     * Refresh token.
     */
    public function refresh(): JsonResponse
    {
        $token = auth('salon_admin')->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }
}
