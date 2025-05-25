<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckUserRequest;
use App\Http\Requests\CompleteProfileRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * ثبت‌نام کاربر و ارسال کد OTP
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $mobile = $request->mobile;

            $otp = AuthService::generateOtp($mobile);

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید با موفقیت ارسال شد.',
                'data' => [
                    'mobile' => $mobile,
                    'otp' => $otp, // فقط برای دولوپ اینجاست برداشته میشه Nimainjast
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     verify otp
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $this->authService->verifyOtp($request->mobile, $request->code);
            $token = auth('api')->login($user);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید با موفقیت تایید شد.',
                'data' => [
                    'token' => $token,
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * CompleteProfileRequest
     *
     * @param CompleteProfileRequest $request
     * @return JsonResponse
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = auth('api')->user();

            // اعتبارسنجی دسته‌بندی کسب و کار
            if ($request->has('business_category_id')) {
                $categoryId = $request->business_category_id;
                $category = BusinessCategory::find($categoryId);

                if (!$category) {
                    throw new \Exception('دسته‌بندی کسب و کار انتخاب شده معتبر نیست.');
                }
            }

            if ($request->has('business_subcategory_id')) {
                $subcategoryId = $request->business_subcategory_id;
                $subcategory = BusinessSubcategory::find($subcategoryId);

                if (!$subcategory) {
                    throw new \Exception('زیرمجموعه کسب و کار انتخاب شده معتبر نیست.');
                }

                if ($request->has('business_category_id') && $subcategory->category_id != $request->business_category_id) {
                    throw new \Exception('زیرمجموعه انتخاب شده با دسته‌بندی مطابقت ندارد.');
                }
            }

            $user = $this->authService->completeProfile($user, $request->validated());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'پروفایل با موفقیت تکمیل شد.',
                'data' => [
                    'user' => $user,
                    'salon' => $user->activeSalon
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * LoginRequest  with pass word
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $loginData = $this->authService->login($request->mobile, $request->password);

            return response()->json([
                'status' => 'success',
                'message' => 'ورود با موفقیت انجام شد.',
                'data' => $loginData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 401);
        }
    }

    /**
     * ForgotPassword Request OTP
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $mobile = $request->mobile;

            $otp = $this->authService->generateOtp($mobile);

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید با موفقیت ارسال شد.',
                'data' => [
                    'mobile' => $mobile,
                    'otp' => $otp, // فقط برای دولوپ اینجاست برداشته میشه Nimainjast
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * ResetPasswordRequest
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $this->authService->resetPassword(
                $request->mobile,
                $request->code,
                $request->password
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'رمز عبور با موفقیت بازیابی شد.',
                'data' => [
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * refreshToken
     *
     * @return JsonResponse
     */
    public function refreshToken(): JsonResponse
    {
        try {
            $token = $this->authService->refreshToken();

            return response()->json([
                'status' => 'success',
                'message' => 'توکن با موفقیت تازه‌سازی شد.',
                'data' => [
                    'token' => $token
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 401);
        }
    }

    /**
     *logout
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'status' => 'success',
                'message' => 'خروج با موفقیت انجام شد.',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * CheckUserRequest
     *
     * @param CheckUserRequest $request
     * @return JsonResponse
     */
    public function checkUser(CheckUserRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->checkUser($request->mobile);

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'mobile' => $request->mobile,
                    'user_exists' => $result['exists'],
                    'has_password' => $result['has_password'],
                    'next_step' => $result['exists'] ?
                        ($result['has_password'] ? 'login' : 'verify_otp') :
                        'register'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
