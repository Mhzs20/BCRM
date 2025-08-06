@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-8">
        <div class="py-8">
            <div>
                <h2 class="text-2xl font-semibold leading-tight">ویرایش اعلان</h2>
            </div>
            <div class="mt-4">
                <form action="{{ route('admin.notifications.update', $notification->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="title" class="block text-gray-700 text-sm font-bold mb-2">عنوان:</label>
                        <input type="text" name="title" id="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ $notification->title }}" required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-gray-700 text-sm font-bold mb-2">پیام:</label>
                        <textarea name="message" id="message" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>{{ $notification->message }}</textarea>
                    </div>
                    <div class="mb-4">
                        <input type="checkbox" name="is_important" id="is_important" class="mr-2 leading-tight" {{ $notification->is_important ? 'checked' : '' }}>
                        <label for="is_important" class="text-sm text-gray-700">مهم</label>
                    </div>
                    <div class="flex items-center justify-between mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                            ذخیره
                        </button>
                        <a href="{{ route('admin.notifications.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            بازگشت
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
