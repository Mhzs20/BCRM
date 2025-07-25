<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\CheckUserRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\CompleteProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use App\Services\SmsService;
use App\Models\User;
use App\Models\UserSmsBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Hekmatinasser\Verta\Verta;
class AuthController extends Controller
{
    protected AuthService $authService;
    protected SmsService $smsService;

    public function __construct(AuthService $authService, SmsService $smsService)
    {
        $this->authService = $authService;
        $this->smsService = $smsService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $mobile = $request->mobile;
            Log::info("AuthController::register - Registration attempt for mobile: {$mobile}");

            $otp = $this->authService->generateOtp($mobile); // متد generateOtp دیگر static نیست

            $userForSms = User::firstWhere('mobile', $mobile);
            if ($userForSms) {
                Log::info("AuthController::register - Sending OTP {$otp} to mobile: {$mobile} for user ID: {$userForSms->id}");
                $this->smsService->sendOtp($mobile, $otp);
            } else {
                Log::error("AuthController::register - User not found after generateOtp for mobile: {$mobile}. SMS not sent.");
            }

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید با موفقیت ارسال شد.',
                'data' => [
                    'mobile' => $mobile,
                    // 'otp_for_dev' => $otp, // در محیط پروداکشن حذف شود
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::register - Exception for mobile {$request->mobile}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        Log::info("AuthController::verifyOtp - Verification attempt for mobile: {$request->mobile}, code: {$request->code}");
        try {
            DB::beginTransaction();

            $user = $this->authService->verifyOtp($request->mobile, $request->code);
            $token = auth('api')->login($user);

            UserSmsBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => (int)env('INITIAL_SMS_BALANCE', 20)]
            );
            Log::info("AuthController::verifyOtp - SMS balance checked/created for user ID: {$user->id}");

            DB::commit();
            Log::info("AuthController::verifyOtp - OTP verified successfully for user ID: {$user->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید با موفقیت تایید شد.',
                'data' => [
                    'token' => $token,
                    'user' => $user->load('activeSalon', 'salons', 'smsBalance'),
                    'has_profile_completed' => !empty($user->name) && !empty($user->password) && !empty($user->business_name) // شرط تکمیل پروفایل می‌تواند دقیق‌تر باشد
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AuthController::verifyOtp - Exception for mobile {$request->mobile}: " . $e->getMessage() . ' --- Trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        Log::info("AuthController::completeProfile - Attempt by user ID: " . Auth::id());
        try {
            DB::beginTransaction();
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $updatedUser = $this->authService->completeProfile($user, $request->validated());
            Log::info("AuthController::completeProfile - Profile completed for user ID: {$updatedUser->id}");

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'پروفایل با موفقیت تکمیل شد.',
                'data' => [
                    'user' => $updatedUser->load('activeSalon', 'salons', 'smsBalance'),
                    'salon' => $updatedUser->activeSalon
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AuthController::completeProfile - Exception for user ID " . Auth::id() . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تکمیل پروفایل: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }


    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $mobile = $request->mobile;
            Log::info("AuthController::forgotPassword - Request for mobile: {$mobile}");

            $otp = $this->authService->generateOtp($mobile); // متد generateOtp دیگر static نیست

            $userForSms = User::firstWhere('mobile', $mobile);
            if ($userForSms) {
                Log::info("AuthController::forgotPassword - Sending OTP {$otp} for password reset to mobile: {$mobile}");
                $this->smsService->sendOtp($mobile, $otp);
            } else {
                Log::error("AuthController::forgotPassword - User not found for mobile: {$mobile}, though generateOtp should handle this or request validation should fail.");
            }

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید برای بازیابی رمز عبور ارسال شد.',
                'data' => [
                    'mobile' => $mobile,
                    // 'otp_for_dev' => $otp, // برای دولوپ
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::forgotPassword - Exception for mobile {$request->mobile}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        Log::info("AuthController::login - Attempt for mobile: {$request->mobile}");
        try {
            $loginData = $this->authService->login($request->mobile, $request->password);
            Log::info("AuthController::login - Successful login for user ID: {$loginData['user']->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'ورود با موفقیت انجام شد.',
                'data' => $loginData
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::login - Failed login for mobile {$request->mobile}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 401);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        Log::info("AuthController::resetPassword - Attempt for mobile: {$request->mobile}");
        try {
            DB::beginTransaction();
            $user = $this->authService->resetPassword(
                $request->mobile,
                $request->code,
                $request->password
            );
            DB::commit();
            Log::info("AuthController::resetPassword - Password reset successfully for user ID: {$user->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'رمز عبور با موفقیت بازیابی شد.',
                'data' => [
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AuthController::resetPassword - Exception for mobile {$request->mobile}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function refreshToken(): JsonResponse
    {
        Log::info("AuthController::refreshToken - Attempt by user ID: " . (Auth::check() ? Auth::id() : 'Guest'));
        try {
            $token = $this->authService->refreshToken();
            Log::info("AuthController::refreshToken - Token refreshed successfully for user ID: " . Auth::id());
            return response()->json([
                'status' => 'success',
                'message' => 'توکن با موفقیت تازه‌سازی شد.',
                'data' => [
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::refreshToken - Exception for user ID " . (Auth::check() ? Auth::id() : 'Guest') . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'امکان تازه‌سازی توکن وجود ندارد: ' . $e->getMessage(),
                'data' => null
            ], 401);
        }
    }

    public function logout(): JsonResponse
    {
        $userId = Auth::check() ? Auth::id() : 'N/A';
        Log::info("AuthController::logout - Attempt by user ID: {$userId}");
        try {
            $this->authService->logout();
            Log::info("AuthController::logout - User ID: {$userId} logged out successfully.");
            return response()->json([
                'status' => 'success',
                'message' => 'خروج با موفقیت انجام شد.',
                'data' => null
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::logout - Exception for user ID {$userId}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خروج از حساب کاربری: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    public function checkUser(CheckUserRequest $request): JsonResponse
    {
        $mobile = $request->input('mobile');
        Log::info("AuthController::checkUser - Request for mobile: {$mobile}");

        try {
            $result = $this->authService->checkUser($mobile);

            Log::info("AuthController::checkUser - Result for mobile {$mobile}: " . json_encode($result));

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'mobile' => $mobile,
                    'next_step' => $result['status']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::checkUser - Exception for mobile {$mobile}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load([
            'salons',
            'smsBalance',
            'activeSalon.province',
            'activeSalon.city',
            'activeSalon.businessCategory',
            'activeSalon.businessSubcategory'
        ]);

        // Eager load counts for the active salon if it exists
        if ($user->activeSalon) {
            $user->activeSalon->loadCount(['customers', 'appointments']);
        }

        // Also load counts for all salons
        $user->salons->each(function ($salon) {
            $salon->loadCount(['customers', 'appointments']);
        });

        return response()->json([
            'success' => true,
            'data' => $user->toArray()
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            /** @var \App\Models\User $user */
            $user = Auth::user();
            $salon = $user->activeSalon;

            $validatedData = $request->validated();
            $userData = $validatedData['user'] ?? [];
            $salonData = $validatedData['salon'] ?? [];

            // Update User
            if (!empty($userData)) {
                if (isset($userData['new_password'])) {
                    if (!isset($userData['current_password']) || !Hash::check($userData['current_password'], $user->password)) {
                        return response()->json(['status' => 'error', 'message' => 'رمز عبور فعلی صحیح نیست.'], 422);
                    }
                    $userData['password'] = Hash::make($userData['new_password']);
                }
                // Unset password confirmation fields
                unset($userData['current_password'], $userData['new_password'], $userData['new_password_confirmation']);

                // Convert Jalali date_of_birth to Gregorian if present
                if (isset($userData['date_of_birth'])) {
                    try {
                        $v = Verta::parse($userData['date_of_birth']);
                        $userData['date_of_birth'] = $v->toCarbon()->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::error("AuthController::updateProfile - Invalid Jalali date_of_birth for user ID " . Auth::id() . ": " . $e->getMessage());
                        return response()->json(['status' => 'error', 'message' => 'فرمت تاریخ تولد شمسی معتبر نیست.'], 422);
                    }
                }

                $user->update($userData);
            }

            // Update Salon
            if ($salon && !empty($salonData)) {
                if ($request->hasFile('salon.image')) {
                    if ($salon->image) {
                        Storage::disk('public')->delete($salon->getRawOriginal('image'));
                    }
                    $imagePath = $request->file('salon.image')->store('salon_images', 'public');
                    $salonData['image'] = $imagePath;
                }

                if ($request->boolean('salon.remove_image')) {
                    if ($salon->image) {
                        Storage::disk('public')->delete($salon->getRawOriginal('image'));
                        $salonData['image'] = null;
                    }
                }
                unset($salonData['remove_image']);

                if (isset($salonData['latitude'])) {
                    $salonData['lat'] = $salonData['latitude'];
                    unset($salonData['latitude']);
                }
                if (isset($salonData['longitude'])) {
                    $salonData['lang'] = $salonData['longitude'];
                    unset($salonData['longitude']);
                }

                $salon->update($salonData);

                // Sync salon's business category with user
                $userUpdateData = [];
                if (isset($salonData['business_category_id'])) {
                    $userUpdateData['business_category_id'] = $salonData['business_category_id'];
                }
                if (isset($salonData['business_subcategory_id'])) {
                    $userUpdateData['business_subcategory_id'] = $salonData['business_subcategory_id'];
                }

                if (!empty($userUpdateData)) {
                    $user->update($userUpdateData);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'پروفایل با موفقیت به‌روزرسانی شد.',
                'data' => $user->load('salons', 'smsBalance', 'activeSalon.province', 'activeSalon.city', 'activeSalon.businessCategory', 'activeSalon.businessSubcategory')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("AuthController::updateProfile - Exception for user ID " . Auth::id() . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در به‌روزرسانی پروفایل: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info("AuthController::changePassword - Password changed successfully for user ID: {$user->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'رمز عبور با موفقیت تغییر کرد.',
            ]);
        } catch (\Exception $e) {
            Log::error("AuthController::changePassword - Exception for user ID " . Auth::id() . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تغییر رمز عبور: ' . $e->getMessage(),
            ], 500);
        }
    }
}
