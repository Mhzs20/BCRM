<?php

namespace App\Services;

use App\Models\User;
use App\Models\Salon;
use App\Models\UserSmsBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\SmsService;
class AuthService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * generateOtp
     *
     * @param string $mobile
     * @return string
     */
    public function generateOtp(string $mobile): string
    {
        $cacheKey = 'otp_attempts_' . $mobile;
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            throw new \Exception('محدودیت ارسال کد تایید. لطفا ۵ دقیقه دیگر تلاش کنید.');
        }
        Cache::put($cacheKey, $attempts + 1, now()->addMinutes(5));

        $otp = (string)mt_rand(1000, 9999);
        $expirationMinutes = (int)env('OTP_EXPIRATION_MINUTES_INT', 2);
        $expiresAt = now()->addMinutes($expirationMinutes);

        Log::info("AuthService::generateOtp - Generating OTP for mobile: {$mobile}. OTP: {$otp}.");

        $user = User::updateOrCreate(
            ['mobile' => $mobile],
            [
                'otp_code' => $otp,
                'otp_expires_at' => $expiresAt
            ]
        );

        $this->smsService->sendOtp($mobile, $otp, $user);

        return $otp;
    }

    /**
     * verifyOtp
     *
     * @param string $mobile
     * @param string $code
     * @return User
     * @throws \Exception
     */
    public function verifyOtp(string $mobile, string $code): User
    {
        Log::info("AuthService::verifyOtp - Attempting to verify OTP for mobile: {$mobile}, code: {$code}");

        $user = User::where('mobile', $mobile)
            ->where('otp_code', $code)
            ->first();

        if (!$user) {
            Log::warning("AuthService::verifyOtp - User not found or OTP code mismatch. Mobile: {$mobile}, Code: {$code}");
            throw new \Exception('کد تایید اولیه نامعتبر است (کاربر یا کد یافت نشد).');
        }

        Log::info("AuthService::verifyOtp - User found: ID {$user->id}. Checking OTP expiration.");

        $otpExpiresAt = $user->otp_expires_at;

        if (is_null($otpExpiresAt)) {
            Log::error("AuthService::verifyOtp - otp_expires_at is NULL for user: {$user->id}. This is unexpected if OTP was just generated.");
            throw new \Exception('خطا در تاریخ انقضای کد (NULL). لطفا مجددا تلاش کنید.');
        }
        if (!($otpExpiresAt instanceof Carbon)) {
            $type = gettype($otpExpiresAt);
            $valueForLog = is_object($otpExpiresAt) ? get_class($otpExpiresAt) : print_r($otpExpiresAt, true);
            Log::critical("AuthService::verifyOtp - CRITICAL: otp_expires_at is NOT a Carbon instance after Eloquent cast for user: {$user->id}. Type: {$type}. Value: {$valueForLog}");
            throw new \Exception('فرمت داخلی تاریخ انقضای کد در برنامه نامعتبر است (پس از cast).');
        }

        Log::info("AuthService::verifyOtp - otp_expires_at for user {$user->id} is a Carbon instance with value: " . $otpExpiresAt->toDateTimeString());

        $currentTime = Carbon::now();
        Log::info("AuthService::verifyOtp - Current time for comparison: " . $currentTime->toDateTimeString());

        if ($otpExpiresAt <= $currentTime) {
            Log::warning("AuthService::verifyOtp - OTP EXPIRED for user: {$user->id}. Expires at: {$otpExpiresAt->toDateTimeString()}, Current time: {$currentTime->toDateTimeString()}");
            throw new \Exception('کد تایید منقضی شده است. زمان انقضا: ' . $otpExpiresAt->format('Y-m-d H:i:s'));
        }

        Log::info("AuthService::verifyOtp - OTP is valid and not expired for user: {$user->id}. Updating user OTP details.");

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        Log::info("AuthService::verifyOtp - User {$user->id} OTP details cleared successfully.");
        return $user;
    }

    public function completeProfile(User $user, array $data): User
    {
        Log::info("AuthService::completeProfile - Starting profile completion for user ID: {$user->id}. Data received: ", $data);

        $userDataToUpdate = [
            'name' => $data['name']
        ];

        if (isset($data['password'])) {
            $userDataToUpdate['password'] = Hash::make($data['password']);
        }

        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            try {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                    Log::info("AuthService::completeProfile - Deleted old avatar for user ID: {$user->id}, path: {$user->avatar}");
                }
                $avatarPath = $data['avatar']->store('avatars/users', 'public');
                $userDataToUpdate['avatar'] = $avatarPath;
                Log::info("AuthService::completeProfile - Stored new avatar for user ID: {$user->id}, path: {$avatarPath}");
            } catch (\Exception $e) {
                Log::error("AuthService::completeProfile - Failed to store avatar for user ID: {$user->id}. Error: " . $e->getMessage());
            }
        }

        if (isset($data['business_name'])) {
            $userDataToUpdate['business_name'] = $data['business_name'];
        }
        if (isset($data['business_category_id'])) {
            $userDataToUpdate['business_category_id'] = $data['business_category_id'];
        }
        if (array_key_exists('business_subcategory_ids', $data) && is_array($data['business_subcategory_ids'])) {
            $userDataToUpdate['business_subcategory_ids'] = $data['business_subcategory_ids'];
        }

        $user->update($userDataToUpdate);
        Log::info("AuthService::completeProfile - User profile data updated for user ID: {$user->id}");


        $salonData = [
            'user_id' => $user->id,
            'name' => $data['business_name'],
            'business_category_id' => $data['business_category_id'],
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'address' => $data['address'],
            'credit_score' => 0,
        ];

        if (isset($data['business_subcategory_ids']) && is_array($data['business_subcategory_ids'])) {
            $salonData['business_subcategory_ids'] = $data['business_subcategory_ids'];
        }

        $optionalFields = [
            'support_phone_number',
            'bio',
            'instagram',
            'telegram',
            'website',
            'whatsapp',
        ];

        foreach ($optionalFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                // Map latitude to lat and longitude to lang for the Salon model
                if ($field === 'latitude') {
                    $salonData['lat'] = $data[$field];
                } elseif ($field === 'longitude') {
                    $salonData['lang'] = $data[$field];
                } else {
                    $salonData[$field] = $data[$field];
                }
            }
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            try {
                $salonImagePath = $data['image']->store('salon_images', 'public');
                $salonData['image'] = $salonImagePath;
                Log::info("AuthService::completeProfile - Stored new salon image for user ID: {$user->id}, path: {$salonImagePath}");
            } catch (\Exception $e) {
                Log::error("AuthService::completeProfile - Failed to store salon image for user ID: {$user->id}. Error: " . $e->getMessage());
            }
        }

        $salon = Salon::create($salonData);
        Log::info("AuthService::completeProfile - New salon CREATED for user ID: {$user->id}, Salon ID: {$salon->id}, Address: {$data['address']}");

        $user->active_salon_id = $salon->id;
        $user->save();
        Log::info("AuthService::completeProfile - Salon ID: {$salon->id} set as active for user ID: {$user->id}");

        UserSmsBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => (int)env('INITIAL_SMS_BALANCE', 20)]
        );
        Log::info("AuthService::completeProfile - SMS balance checked/created for user ID: {$user->id}");

        Log::info("AuthService::completeProfile - Profile completion finished for user ID: {$user->id}");
        return $user->fresh()->load('activeSalon', 'salons', 'smsBalance');
    }

    public function login(string $mobile, string $password): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user || !$user->password) {
            throw new \Exception('کاربر یافت نشد یا پروفایل کاربر تکمیل نشده و فاقد رمز عبور است.');
        }

        if (!Hash::check($password, $user->password)) {
            throw new \Exception('رمز عبور اشتباه است.');
        }

        $token = auth('api')->login($user);
        $user->load(['salons', 'activeSalon.businessCategory', 'activeSalon.city.province', 'smsBalance']);

        return [
            'user' => $user,
            'token' => $token,
            'salons' => $user->salons,
            'salons_count' => $user->salons->count(),
            'active_salon' => $user->activeSalon
        ];
    }

    public function resetPassword(string $mobile, string $code, string $password): User
    {
        $user = $this->verifyOtp($mobile, $code);

        $user->update([
            'password' => Hash::make($password),
        ]);

        return $user;
    }

    public function refreshToken(): string
    {
        return (string) auth('api')->refresh();
    }

    public function logout(): bool
    {
        auth('api')->logout();
        return true;
    }

    public function checkUser(string $mobile): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user) {
            $this->generateOtp($mobile);
            return [
                'status' => 'new_user',
                'message' => 'کاربر یافت نشد. کد تایید برای شروع ثبت‌نام ارسال شد.'
            ];
        }

        if (is_null($user->password)) {
            $this->generateOtp($user->mobile);
            return [
                'status' => 'incomplete_profile',
                'message' => 'ثبت‌نام شما ناقص است. کد تایید برای تکمیل فرآیند ارسال شد.'
            ];
        }

        if (empty($user->name) || empty($user->business_category_id)) {
            $this->generateOtp($user->mobile);
            return [
                'status' => 'complete_profile_required',
                'message' => 'پروفایل شما کامل نیست. کد تایید برای ادامه ارسال شد.'
            ];
        }

        return [
            'status' => 'login_required',
            'message' => 'کاربر یافت شد. لطفا رمز عبور را برای ورود وارد کنید.'
        ];
    }
}
