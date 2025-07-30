@extends('admin.layouts.app')

@section('title', 'ویرایش قالب SMS')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        ویرایش قالب SMS: {{ $smsTemplate->name }}
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('admin.sms-templates.update', $smsTemplate) }}" method="POST">
                        @method('PUT')
                        @include('admin.sms-templates._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
