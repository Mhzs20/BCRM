<div id="resetPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center px-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">بازنشانی رمز عبور</h3>
        <form id="resetPasswordForm" method="POST">
            @csrf
            <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">رمز عبور جدید</label>
                <input type="password" name="new_password" id="new_password" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>
            <div class="mb-6">
                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">تایید رمز عبور جدید</label>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>
            <div class="flex justify-end space-x-3 space-x-reverse">
                <button type="button" onclick="closeModal('resetPasswordModal')" class="btn-secondary">
                    لغو
                </button>
                <button type="submit" class="btn-action bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500">
                    بازنشانی
                </button>
            </div>
        </form>
    </div>
</div>
