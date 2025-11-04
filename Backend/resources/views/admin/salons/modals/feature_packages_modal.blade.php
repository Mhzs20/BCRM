{{-- Feature Packages Modal --}}
<div id="featurePackagesModal" class="modal-overlay fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 hidden" style="z-index: 999999 !important; position: fixed !important;">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[75vh] overflow-hidden" style="z-index: 1000000 !important;">
        <!-- Header -->
        <div class="p-5 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="ri-gift-line text-emerald-600 ml-2"></i>
                    مدیریت پکیج امکانات - سالن: {{ $salon->name }}
                </h3>
                <button type="button" onclick="closeModal('featurePackagesModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>
        </div>

        <!-- Scrollable Content -->
        <div class="p-5 overflow-y-auto" style="max-height: calc(75vh - 140px);">

            {{-- Loading State --}}
            <div id="featurePackagesLoading" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600"></div>
                <p class="mt-2 text-gray-600">در حال بارگذاری پکیج‌های امکانات...</p>
            </div>

            {{-- Content --}}
            <div id="featurePackagesContent" class="hidden">
                {{-- Current Package Status --}}
                <div id="currentPackageSection" class="mb-6 p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                    <h4 class="text-lg font-semibold text-emerald-900 mb-3">
                        <i class="ri-shield-check-line ml-2"></i>
                        وضعیت پکیج فعلی
                    </h4>
                    <div id="currentPackageDisplay">
                        <!-- Current package info will be loaded here -->
                    </div>
                </div>

                {{-- Available Packages --}}
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="ri-list-check ml-2"></i>
                        پکیج‌های در دسترس
                    </h4>
                    <div id="packagesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Packages will be loaded here via JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-5 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <i class="ri-information-line ml-1"></i>
                    با فعال‌سازی پکیج جدید، پکیج فعلی غیرفعال خواهد شد
                </div>
                <div class="flex gap-3">
                    <button type="button" id="deactivatePackageBtn" onclick="deactivateCurrentPackage({{ $salon->id }})" class="hidden bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                        <i class="ri-close-circle-line ml-1"></i>
                        غیرفعال کردن پکیج فعلی
                    </button>
                    <button type="button" onclick="closeModal('featurePackagesModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Package Activation Confirmation Modal --}}
<div id="packageActivationModal" class="modal-overlay fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 hidden" style="z-index: 999999 !important; position: fixed !important;">
    <div class="modal-content bg-white rounded-lg shadow-xl w-full max-w-md" style="z-index: 1000000 !important;">
        <div class="p-5">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">تأیید فعال‌سازی پکیج</h3>
                    <button type="button" onclick="closeModal('packageActivationModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>

                <div id="activationModalContent">
                    <div class="mb-4">
                        <p class="text-gray-700 mb-3">آیا مطمئن هستید که می‌خواهید پکیج <strong id="selectedPackageName"></strong> را برای این سالن فعال کنید؟</p>
                        
                        <div class="mb-3">
                            <label for="durationMonths" class="block text-sm font-medium text-gray-700 mb-1">مدت اعتبار (ماه)</label>
                            <select id="durationMonths" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="1">1 ماه</option>
                                <option value="2">2 ماه</option>
                                <option value="3" selected>3 ماه</option>
                                <option value="6">6 ماه</option>
                                <option value="12">12 ماه</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeModal('packageActivationModal')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            انصراف
                        </button>
                        <button type="button" id="confirmActivationBtn" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded">
                            <i class="ri-check-line ml-1"></i>
                            تأیید فعال‌سازی
                        </button>
                    </div>
                </div>

                <div id="activationLoadingState" class="hidden text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-emerald-600"></div>
                    <p class="mt-2 text-gray-600">در حال فعال‌سازی پکیج...</p>
                </div>
            </div>
        </div>
    </div>
</div>