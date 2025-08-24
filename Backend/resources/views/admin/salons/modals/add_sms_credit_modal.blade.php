<div id="addSmsCreditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center px-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">افزودن اعتبار پیامک</h3>
        <form id="smsCreditForm" method="POST">
            @csrf
            <input type="hidden" name="salon_id" id="modalSalonIdSms">
            <div class="mb-4">
                <label for="smsCreditAmount" class="block text-sm font-medium text-gray-700 mb-2">تعداد پیامک</label>
                <input type="number" name="amount" id="smsCreditAmount" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="mb-4">
                <label for="smsCreditDescription" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="smsCreditDescription" rows="4" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end space-x-3 space-x-reverse">
                <button type="button" onclick="closeModal('addSmsCreditModal')" class="btn-secondary">
                    لغو
                </button>
                <button type="submit" class="btn-action bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500">
                    ثبت
                </button>
            </div>
        </form>
    </div>
</div>
