@extends('admin.layouts.app')

@section('title', 'تنظیمات سیستم رفرال')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">تنظیمات سیستم رفرال</h1>
            <p class="text-gray-600">پیکربندی قوانین و مقادیر سیستم رفرال</p>
        </div>

        <form method="POST" action="{{ route('admin.referral.settings.update') }}" x-data="settingsForm">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- General Settings -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="ri-settings-line text-blue-600 ml-3"></i>تنظیمات عمومی
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- System Status -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" 
                                       {{ $settings->is_active ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="mr-3 text-sm font-medium text-gray-700">فعال بودن سیستم رفرال</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">در صورت غیرفعال بودن، کاربران نمی‌توانند رفرال جدید ایجاد کنند</p>
                        </div>

                        <!-- Reward Types (Multiple Selection) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                انواع پاداش دهی
                            </label>
                            <p class="text-xs text-gray-500 mb-4">می‌توانید هر دو نوع پاداش را همزمان فعال کنید</p>
                            
                            <div class="space-y-3">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50" 
                                       :class="{ 'border-blue-500 bg-blue-50': enableSignupReward }">
                                    <input type="checkbox" name="enable_signup_reward" value="1" 
                                           {{ $settings->enable_signup_reward ? 'checked' : '' }}
                                           class="text-blue-600 focus:ring-blue-500" 
                                           x-model="enableSignupReward">
                                    <div class="mr-3">
                                        <span class="font-medium text-gray-900">مبلغ ثابت به ازای هر دعوت</span>
                                        <p class="text-sm text-gray-500">به ازای هر دعوت موفق، مبلغ ثابتی پرداخت شود</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                                       :class="{ 'border-blue-500 bg-blue-50': enablePurchaseReward }">
                                    <input type="checkbox" name="enable_purchase_reward" value="1" 
                                           {{ $settings->enable_purchase_reward ? 'checked' : '' }}
                                           class="text-blue-600 focus:ring-blue-500"
                                           x-model="enablePurchaseReward">
                                    <div class="mr-3">
                                        <span class="font-medium text-gray-900">درصد از مبلغ خرید</span>
                                        <p class="text-sm text-gray-500">به ازای هر خرید دعوت‌شده، درصدی از مبلغ پرداخت شود</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Fixed Amount Settings -->
                        <div x-show="enableSignupReward" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                مبلغ پاداش هر دعوت (ریال)
                            </label>
                            <input type="number" name="referral_reward_amount" 
                                   value="{{ $settings->referral_reward_amount }}" 
                                   min="0" step="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">مبلغی که به ازای هر دعوت موفق پرداخت می‌شود</p>
                        </div>

                        <!-- Percentage Settings -->
                        <div x-show="enablePurchaseReward" x-transition>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        درصد پاداش خرید (%)
                                    </label>
                                    <input type="number" name="order_reward_percentage" 
                                           value="{{ $settings->order_reward_percentage }}" 
                                           min="0" max="100" step="0.1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">درصدی از مبلغ خرید که به دعوت‌کننده پرداخت می‌شود</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        حداکثر پاداش خرید (ریال)
                                    </label>
                                    <input type="number" name="max_order_reward" 
                                           value="{{ $settings->max_order_reward }}" 
                                           min="0" step="1000"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">حداکثر مبلغ پاداش برای هر خرید (خالی = بدون محدودیت)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        حداقل مبلغ خرید برای پاداش (ریال)
                                    </label>
                                    <input type="number" name="min_purchase_amount" 
                                           value="{{ $settings->min_purchase_amount ?? 0 }}" 
                                           min="0" step="1000"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">حداقل مبلغ خرید برای دریافت پاداش درصدی</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="ri-settings-4-line text-purple-600 ml-3"></i>تنظیمات تکمیلی
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- Maximum Referrals -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                حداکثر تعداد دعوت در ماه
                            </label>
                            <input type="number" name="max_referrals_per_month" 
                                   value="{{ $settings->max_referrals_per_month ?? 0 }}" 
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">صفر = بدون محدودیت</p>
                        </div>

                        <!-- Minimum Purchase -->
                        <div x-show="enablePurchaseReward" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                حداقل مبلغ خرید برای پاداش درصدی (ریال)
                            </label>
                            <input type="number" name="min_purchase_amount" 
                                   value="{{ $settings->min_purchase_amount ?? 0 }}" 
                                   min="0" step="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">حداقل مبلغ خرید برای دریافت پاداش درصدی</p>
                        </div>
                        
                        <!-- SMS Notifications -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="send_sms_notifications" value="1" 
                                       {{ $settings->send_sms_notifications ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                                <span class="mr-3 text-sm font-medium text-gray-700">ارسال اعلان‌های پیامک</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">ارسال پیامک به دعوت‌کننده هنگام دریافت پاداش</p>
                        </div>
                    </div>
                </div>

                <!-- Limits and Restrictions -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="ri-shield-check-line text-orange-600 ml-3"></i>محدودیت‌ها و قوانین
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- Max Referrals Per Day -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                حداکثر دعوت روزانه
                            </label>
                            <input type="number" name="daily_referral_limit" 
                                   value="{{ $settings->daily_referral_limit ?? 0 }}" 
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">تعداد دعوت‌نامه‌ای که هر کاربر در روز می‌تواند ارسال کند (0 = بدون محدودیت)</p>
                        </div>

                        <!-- Max Referrals Per Month -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                حداکثر دعوت ماهانه
                            </label>
                            <input type="number" name="max_referrals_per_month" 
                                   value="{{ $settings->max_referrals_per_month }}" 
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">تعداد دعوت‌نامه‌ای که هر کاربر در ماه می‌تواند ارسال کند (0 = بدون محدودیت)</p>
                        </div>

                        <!-- Max Referrals Total -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                حداکثر دعوت کلی
                            </label>
                            <input type="number" name="total_referral_limit" 
                                   value="{{ $settings->total_referral_limit ?? 0 }}" 
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">حداکثر تعداد کل دعوت‌نامه‌ای که هر کاربر می‌تواند ارسال کند (0 = بدون محدودیت)</p>
                        </div>

                        <!-- Referral Expiry Days -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                مدت اعتبار دعوت‌نامه (روز)
                            </label>
                            <input type="number" name="referral_expiry_days" 
                                   value="{{ $settings->referral_expiry_days }}" 
                                   min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">تعداد روزی که دعوت‌شده باید ثبت‌نام کند (خالی = بدون محدودیت زمانی)</p>
                        </div>

                        <!-- Minimum Order Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                حداقل مبلغ خرید برای پاداش (ریال)
                            </label>
                            <input type="number" name="min_order_amount_for_reward" 
                                   value="{{ $settings->min_order_amount_for_reward }}" 
                                   min="0" step="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">حداقل مبلغ خرید دعوت‌شده برای دریافت پاداش</p>
                        </div>
                    </div>
                </div>

                <!-- Welcome Bonus Settings -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="ri-gift-line text-green-600 ml-3"></i>پاداش خوشامدگویی
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- Welcome Bonus -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                پاداش خوشامدگویی (ریال)
                            </label>
                            <input type="number" name="welcome_bonus" 
                                   value="{{ $settings->welcome_bonus }}" 
                                   min="0" step="1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">مبلغ پاداش برای کاربران جدید (خالی = بدون پاداش)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="mt-8 flex justify-end">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-colors duration-200 flex items-center">
                    <i class="ri-save-line ml-2"></i>
                    ذخیره تنظیمات
                </button>
            </div>
        </form>

        <!-- Current Settings Summary -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">خلاصه تنظیمات فعلی</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div class="bg-gray-50 p-3 rounded">
                    <strong>وضعیت سیستم:</strong> 
                    <span class="{{ $settings->is_active ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings->is_active ? 'فعال' : 'غیرفعال' }}
                    </span>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <strong>پاداش ثبت نام:</strong> 
                    <span class="{{ $settings->enable_signup_reward ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings->enable_signup_reward ? 'فعال' : 'غیرفعال' }}
                    </span>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <strong>پاداش خرید:</strong> 
                    <span class="{{ $settings->enable_purchase_reward ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings->enable_purchase_reward ? 'فعال' : 'غیرفعال' }}
                    </span>
                </div>
                @if($settings->enable_signup_reward)
                <div class="bg-gray-50 p-3 rounded">
                    <strong>مبلغ پاداش:</strong> 
                    {{ number_format($settings->referral_reward_amount ?? 0) }} ریال
                </div>
                @endif
                @if($settings->enable_purchase_reward)
                <div class="bg-gray-50 p-3 rounded">
                    <strong>درصد پاداش:</strong> 
                    {{ $settings->order_reward_percentage ?? 0 }}%
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <strong>حداکثر پاداش:</strong> 
                    {{ $settings->max_order_reward ? number_format($settings->max_order_reward) . ' ریال' : 'نامحدود' }}
                </div>
                @endif
                <div class="bg-gray-50 p-3 rounded">
                    <strong>اعلان پیامک:</strong> 
                    <span class="{{ $settings->send_sms_notifications ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings->send_sms_notifications ? 'فعال' : 'غیرفعال' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('settingsForm', () => ({
        enableSignupReward: {{ $settings->enable_signup_reward ? 'true' : 'false' }},
        enablePurchaseReward: {{ $settings->enable_purchase_reward ? 'true' : 'false' }},
        init() {
            this.$watch('enableSignupReward', value => {
                this.updateFormDisplay();
            });
            this.$watch('enablePurchaseReward', value => {
                this.updateFormDisplay();
            });
        },
        updateFormDisplay() {
            // Show alert if no reward type is selected
            if (!this.enableSignupReward && !this.enablePurchaseReward) {
                console.log('هیچ نوع پاداشی انتخاب نشده است');
            }
        }
    }));
});
</script>

@endsection