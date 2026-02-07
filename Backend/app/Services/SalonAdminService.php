<?php

namespace App\Services;

use App\Models\SalonAdmin;
use App\Models\AdminOtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SalonAdminService
{
    protected SmsNotificationService $smsService;

    public function __construct(SmsNotificationService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Create a new salon admin.
     */
    public function createAdmin(array $data, int $salonId, int $createdBy): SalonAdmin
    {
        return DB::transaction(function () use ($data, $salonId, $createdBy) {
            // Get mobile from temp token
            $otp = AdminOtpVerification::where('temp_token', $data['temp_token'])
                ->where('is_verified', true)
                ->firstOrFail();

            // Check if mobile is already registered
            $existingAdmin = SalonAdmin::where('mobile', $otp->mobile)->first();
            if ($existingAdmin) {
                throw new \Exception('این شماره موبایل قبلا ثبت شده است.');
            }

            // Create admin
            $admin = SalonAdmin::create([
                'salon_id' => $salonId,
                'created_by' => $createdBy,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'mobile' => $otp->mobile,
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // Sync permissions
            if (!empty($data['permissions'])) {
                $admin->syncPermissions($data['permissions']);
            }

            // Send welcome SMS with credentials
            $this->smsService->sendWelcomeSms($admin, $data['password']);

            // Delete the used OTP
            $otp->delete();

            return $admin->load('permissions');
        });
    }

    /**
     * Update salon admin.
     */
    public function updateAdmin(SalonAdmin $admin, array $data): SalonAdmin
    {
        return DB::transaction(function () use ($admin, $data) {
            // Update basic info
            $admin->update([
                'first_name' => $data['first_name'] ?? $admin->first_name,
                'last_name' => $data['last_name'] ?? $admin->last_name,
                'email' => $data['email'] ?? $admin->email,
                'is_active' => $data['is_active'] ?? $admin->is_active,
            ]);

            // Update permissions if provided
            if (isset($data['permissions'])) {
                $admin->syncPermissions($data['permissions']);
            }

            // Send notification if status changed
            if (isset($data['is_active'])) {
                if ($data['is_active'] && !$admin->getOriginal('is_active')) {
                    $this->smsService->sendActivationSms($admin);
                } elseif (!$data['is_active'] && $admin->getOriginal('is_active')) {
                    $this->smsService->sendDeactivationSms($admin);
                }
            }

            return $admin->fresh(['permissions']);
        });
    }

    /**
     * Reset admin password.
     */
    public function resetPassword(SalonAdmin $admin, string $newPassword): void
    {
        $admin->update([
            'password' => Hash::make($newPassword),
        ]);

        // Send SMS notification
        $this->smsService->sendPasswordResetSms($admin, $newPassword);
    }

    /**
     * Delete admin.
     */
    public function deleteAdmin(SalonAdmin $admin): void
    {
        DB::transaction(function () use ($admin) {
            // Detach all permissions
            $admin->permissions()->detach();

            // Soft delete the admin
            $admin->delete();
        });
    }

    /**
     * Get all admins for a salon with their permissions.
     */
    public function getSalonAdmins(int $salonId, array $filters = [])
    {
        $query = SalonAdmin::with(['permissions', 'creator'])
            ->bySalon($salonId);

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get admin details with permissions.
     */
    public function getAdminDetails(int $adminId, int $salonId): ?SalonAdmin
    {
        return SalonAdmin::with(['permissions', 'creator', 'salon'])
            ->bySalon($salonId)
            ->find($adminId);
    }
}
