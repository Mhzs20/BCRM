@extends('admin.layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-8">
        <div class="py-8">
            <div>
                <h2 class="text-2xl font-semibold leading-tight">ویرایش نحوه آشنایی</h2>
            </div>
            <div class="mt-4">
                <form action="{{ route('admin.how-introduced.update', $howIntroduced->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('admin.how-introduced._form', ['howIntroduced' => $howIntroduced])
                    <div class="flex items-center justify-between mt-6">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                            ذخیره
                        </button>
                        <a href="{{ route('admin.how-introduced.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            بازگشت
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
