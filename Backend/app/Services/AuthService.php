<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * generateOtp
     *
     * @param string $mobile
     * @return string
     */
    public static function generateOtp(string $mobile): string
    {
        // (3 time in 5 min)
        $cacheKey = 'otp_attempts_' . $mobile;
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            throw new \Exception('محدودیت ارسال کد تایید. لطفا ۵ دقیقه دیگر تلاش کنید.');
        }


        Cache::put($cacheKey, $attempts + 1, now()->addMinutes(5));


        $otp = mt_rand(1000, 9999);

        User::updateOrCreate(
            ['mobile' => $mobile],
            [
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(5)
            ]
        );

        //  send with kaveh negar
        // sendSms($mobile, "کد تایید شما: $otp");

        return $otp;
    }

    /**
     * verifyOtp
     *
     * @param string $mobile
     * @param string $code
     * @return User
     */
    public function verifyOtp(string $mobile, string $code): User
    {
        $user = User::where('mobile', $mobile)
            ->where('otp_code', $code)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            throw new \Exception('کد تایید نامعتبر یا منقضی شده است.');
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        return $user;
    }

    /**
     * completeProfile
 *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function completeProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        if ($user->salons()->count() == 0) {
            $salon = new Salon();
            $salon->name = $data['business_name'] ?? $data['name'];
            $salon->user_id = $user->id;
            $salon->business_category_id = $data['business_category_id'] ?? null;
            $salon->business_subcategory_id = $data['business_subcategory_id'] ?? null;
            $salon->credit_score = 0;
            $salon->save();

            $user->active_salon_id = $salon->id;
            $user->save();
        }

        return $user;
    }

    /**
     * login
     *
     * @param string $mobile
     * @param string $password
     * @return array
     */
    public function login(string $mobile, string $password): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user || !$user->password) {
            throw new \Exception('کاربر یافت نشد یا پروفایل کاربر تکمیل نشده است.');
        }

        if (!Hash::check($password, $user->password)) {
            throw new \Exception('رمز عبور اشتباه است.');
        }

        $token = auth('api')->login($user);

        $salons = $user->salons;
        $salonsCount = $salons->count();

        return [
            'user' => $user,
            'token' => $token,
            'salons' => $salons,
            'salons_count' => $salonsCount,
            'active_salon' => $user->activeSalon
        ];
    }

    /**
     * resetPassword
     *
     * @param string $mobile
     * @param string $code
     * @param string $password
     * @return User
     */
    public function resetPassword(string $mobile, string $code, string $password): User
    {
        $user = $this->verifyOtp($mobile, $code);

        $user->update([
            'password' => Hash::make($password),
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        return $user;
    }

    /**
      * refreshToken
     *
     * @return string
     */
    public function refreshToken(): string
    {
        return JWTAuth::refresh();
    }

    /**
     * logout
 *
     * @return bool
     */
    public function logout(): bool
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return true;
    }

    /**
     * checkUser
     *
     * @param string $mobile
     * @return array
     */
    public function checkUser(string $mobile): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            return [
                'exists' => false,
                'has_password' => false,
                'message' => 'کاربر جدید'
            ];
        }

        return [
            'exists' => true,
            'has_password' => !empty($user->password),
            'message' => !empty($user->password) ? 'کاربر با رمز عبور' : 'کاربر بدون رمز عبور'
        ];
    }
}
