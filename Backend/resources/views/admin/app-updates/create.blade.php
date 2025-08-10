@extends('admin.layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-8">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">اضافه کردن ورژن جدید</h2>
            <p class="mt-1 text-sm text-gray-600">
                در این بخش می‌توانید اطلاعات مربوط به ورژن جدید اپلیکیشن را وارد کنید.
            </p>
        </div>
        <div class="my-2 flex sm:flex-row flex-col">
            <a href="{{ route('admin.app-updates.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                <i class="ri-arrow-left-line"></i>
                بازگشت
            </a>
        </div>
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">خطا!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="mt-5 md:mt-0 md:col-span-2">
            <form action="{{ route('admin.app-updates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 bg-white sm:p-6">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-3">
                                <label for="version" class="block text-sm font-medium text-gray-700">ورژن</label>
                                <input type="text" name="version" id="version" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" value="{{ old('version') }}" required>
                            </div>

                            <div class="col-span-6">
                                <label for="direct_link" class="block text-sm font-medium text-gray-700">لینک مستقیم</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        <i class="ri-links-line"></i>
                                    </span>
                                    <input type="url" name="direct_link" id="direct_link" class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" value="{{ old('direct_link') }}">
                                </div>
                            </div>

                            <div class="col-span-6">
                                <label for="google_play_link" class="block text-sm font-medium text-gray-700">لینک گوگل پلی</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        <i class="ri-google-play-line"></i>
                                    </span>
                                    <input type="url" name="google_play_link" id="google_play_link" class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" value="{{ old('google_play_link') }}">
                                </div>
                            </div>

                            <div class="col-span-6">
                                <label for="cafe_bazaar_link" class="block text-sm font-medium text-gray-700">لینک کافه بازار</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        <i class="ri-store-2-line"></i>
                                    </span>
                                    <input type="url" name="cafe_bazaar_link" id="cafe_bazaar_link" class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" value="{{ old('cafe_bazaar_link') }}">
                                </div>
                            </div>

                            <div class="col-span-6">
                                <label for="app_store_link" class="block text-sm font-medium text-gray-700">لینک اپ استور</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        <i class="ri-app-store-line"></i>
                                    </span>
                                    <input type="url" name="app_store_link" id="app_store_link" class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" value="{{ old('app_store_link') }}">
                                </div>
                            </div>

                            <div class="col-span-6">
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea name="notes" id="notes" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('notes') }}</textarea>
                            </div>

                            <div class="col-span-6">
                                <label for="apk_file" class="block text-sm font-medium text-gray-700">فایل APK</label>
                                <input type="file" name="apk_file" id="apk_file" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>

                            <div class="col-span-6">
                                <div class="flex items-center">
                                    <input type="hidden" name="force_update" value="0">
                                    <input type="checkbox" name="force_update" id="force_update" value="1" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" {{ old('force_update') ? 'checked' : '' }}>
                                    <label for="force_update" class="ml-2 block text-sm text-gray-900">Force Update</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="ri-save-line"></i>
                            ذخیره
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
