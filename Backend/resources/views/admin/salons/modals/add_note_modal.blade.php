<div id="addNoteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center px-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
        <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center">افزودن یادداشت</h3>
        <form id="noteForm" method="POST">
            @csrf
            <input type="hidden" name="salon_id" id="modalSalonId">
            <div class="mb-4">
                <label for="noteContent" class="block text-sm font-medium text-gray-700 mb-2">متن یادداشت</label>
                <textarea name="note" id="noteContent" rows="4" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                @error('note') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end space-x-3 space-x-reverse">
                <button type="button" onclick="closeModal('addNoteModal')" class="btn-secondary">
                    لغو
                </button>
                <button type="submit" class="btn-action bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500">
                    ثبت یادداشت
                </button>
            </div>
        </form>
    </div>
</div>
