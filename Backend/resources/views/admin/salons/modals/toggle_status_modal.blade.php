<div id="toggleStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center px-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto">
        <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">تغییر وضعیت سالن</h3>
        <p id="toggleStatusMessage" class="text-base text-gray-700 mb-6 text-center">آیا مطمئن هستید که می‌خواهید وضعیت این سالن را تغییر دهید؟</p>
        <form id="toggleStatusForm" method="POST">
            @csrf
            <div class="flex justify-end space-x-3 space-x-reverse">
                <button type="button" onclick="closeModal('toggleStatusModal')" class="btn-secondary">
                    لغو
                </button>
                <button type="submit" class="btn-action bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500">
                    تایید
                </button>
            </div>
        </form>
    </div>
</div>
