<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompleteRegistrationRequest;
use App\Http\Requests\Admin\UpdateSalonAdminRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Services\SalonAdminService;
use App\Services\OtpService;
use App\Models\SalonAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalonAdminController extends Controller
{
    protected SalonAdminService $adminService;
    protected OtpService $otpService;

    public function __construct(
        SalonAdminService $adminService,
        OtpService $otpService
    ) {
        $this->adminService = $adminService;
        $this->otpService = $otpService;
    }

    /**
     * Complete registration for new admin.
     */
    public function completeRegistration(CompleteRegistrationRequest $request, int $salonId): JsonResponse
    {
        try {
            // Validate temp token
            $otp = $this->otpService->validateTempToken($request->temp_token);
            
            if (!$otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن موقت نامعتبر یا منقضی شده است.',
                ], 400);
            }

            // Get current salon owner/user
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            // Verify user has access to this salon
            if (!$user->salon || $user->salon->id != $salonId) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما دسترسی به این سالن را ندارید.',
                ], 403);
            }

            // Create admin
            $admin = $this->adminService->createAdmin(
                $request->validated(),
                $salonId,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'ادمین با موفقیت ایجاد شد و اطلاعات ورود برای او ارسال شد.',
                'data' => [
                    'id' => $admin->id,
                    'first_name' => $admin->first_name,
                    'last_name' => $admin->last_name,
                    'mobile' => $admin->mobile,
                    'email' => $admin->email,
                    'is_active' => $admin->is_active,
                    'permissions' => $admin->permissions,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get list of all salon admins.
     */
    public function index(Request $request, int $salonId): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
            ], 401);
        }

        // Verify user has access to this salon
        if (!$user->salon || $user->salon->id != $salonId) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سالن را ندارید.',
            ], 403);
        }

        $filters = [
            'is_active' => $request->input('is_active'),
            'search' => $request->input('search'),
            'per_page' => $request->input('per_page', 15),
        ];

        $admins = $this->adminService->getSalonAdmins($salonId, $filters);

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * Get specific admin details.
     */
    public function show(int $salonId, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
            ], 401);
        }

        // Verify user has access to this salon
        if (!$user->salon || $user->salon->id != $salonId) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سالن را ندارید.',
            ], 403);
        }

        $admin = $this->adminService->getAdminDetails($id, $salonId);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'ادمین یافت نشد.',
            ], 404);
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
                'is_active' => $admin->is_active,
                'last_login_at' => $admin->last_login_at,
                'created_at' => $admin->created_at,
                'permissions' => $admin->permissions,
                'creator' => $admin->creator ? [
                    'id' => $admin->creator->id,
                    'name' => $admin->creator->name ?? $admin->creator->mobile,
                ] : null,
            ],
        ]);
    }

    /**
     * Update admin information.
     */
    public function update(UpdateSalonAdminRequest $request, int $salonId, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
            ], 401);
        }

        // Verify user has access to this salon
        if (!$user->salon || $user->salon->id != $salonId) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سالن را ندارید.',
            ], 403);
        }

        $admin = SalonAdmin::bySalon($salonId)->find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'ادمین یافت نشد.',
            ], 404);
        }

        try {
            $updatedAdmin = $this->adminService->updateAdmin($admin, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'اطلاعات ادمین با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'id' => $updatedAdmin->id,
                    'first_name' => $updatedAdmin->first_name,
                    'last_name' => $updatedAdmin->last_name,
                    'mobile' => $updatedAdmin->mobile,
                    'email' => $updatedAdmin->email,
                    'is_active' => $updatedAdmin->is_active,
                    'permissions' => $updatedAdmin->permissions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset admin password.
     */
    public function resetPassword(ResetPasswordRequest $request, int $salonId, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
            ], 401);
        }

        // Verify user has access to this salon
        if (!$user->salon || $user->salon->id != $salonId) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سالن را ندارید.',
            ], 403);
        }

        $admin = SalonAdmin::bySalon($salonId)->find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'ادمین یافت نشد.',
            ], 404);
        }

        try {
            $this->adminService->resetPassword($admin, $request->password);

            return response()->json([
                'success' => true,
                'message' => 'رمز عبور با موفقیت تغییر کرد و برای ادمین ارسال شد.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete admin.
     */
    public function destroy(int $salonId, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
            ], 401);
        }

        // Verify user has access to this salon
        if (!$user->salon || $user->salon->id != $salonId) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این سالن را ندارید.',
            ], 403);
        }

        $admin = SalonAdmin::bySalon($salonId)->find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'ادمین یافت نشد.',
            ], 404);
        }

        try {
            $this->adminService->deleteAdmin($admin);

            return response()->json([
                'success' => true,
                'message' => 'ادمین با موفقیت حذف شد.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
