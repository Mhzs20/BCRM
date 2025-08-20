@extends('admin.layouts.app')

@section('title', 'ویرایش بنر')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        ویرایش بنر
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="image" class="block text-sm font-medium text-gray-700">تصویر</label>
                        <input type="file" name="image" id="image" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="Banner" class="mt-2 h-24 w-24 object-cover rounded-md">
                    </div>
                    <div class="mb-4">
                        <label for="link" class="block text-sm font-medium text-gray-700">لینک</label>
                        <input type="url" name="link" id="link" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" value="{{ old('link', $banner->link) }}">
                    </div>
                    <div class="mb-4">
                        <label for="location" class="block text-sm font-medium text-gray-700">مکان</label>
                        <select name="location" id="location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            <option value="home" {{ $banner->location == 'home' ? 'selected' : '' }}>صفحه اصلی</option>
                            <option value="dashboard" {{ $banner->location == 'dashboard' ? 'selected' : '' }}>داشبورد</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            ذخیره
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
