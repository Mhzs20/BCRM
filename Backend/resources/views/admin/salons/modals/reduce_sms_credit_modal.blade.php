<div id="reduceSmsCreditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center px-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">کاهش اعتبار پیامک</h3>
        <form id="reduceSmsCreditForm" method="POST">
            @csrf
            <input type="hidden" name="salon_id" id="modalSalonIdReduceSms">
            <div class="mb-4">
                <label for="reduceSmsCreditAmount" class="block text-sm font-medium text-gray-700 mb-2">تعداد پیامک</label>
                <input type="number" name="amount" id="reduceSmsCreditAmount" required min="1" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="mb-4">
                <label for="reduceSmsCreditDescription" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                <textarea name="description" id="reduceSmsCreditDescription" rows="4" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm"></textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end space-x-3 space-x-reverse">
                <button type="button" onclick="closeModal('reduceSmsCreditModal')" class="btn-secondary">
                    لغو
                </button>
                <button type="submit" class="btn-action bg-red-600 text-white hover:bg-red-700 focus:ring-red-500">
                    کاهش اعتبار
                </button>
            </div>
        </form>
    </div>
</div>
