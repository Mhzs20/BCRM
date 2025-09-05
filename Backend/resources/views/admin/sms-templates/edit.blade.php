@extends('admin.layouts.app')
@section('title','ویرایش قالب')
@section('header')<h2 class="font-semibold text-xl">ویرایش قالب: {{ $smsTemplate->title }}</h2>@endsection
@section('content')
<div class="py-10">
 <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
  <div class="bg-white shadow sm:rounded-lg p-6">
   <form method="POST" action="{{ route('admin.sms-templates.update', $smsTemplate) }}">
    @csrf
    @method('PUT')
    @include('admin.sms-templates._form')
   </form>
  </div>
 </div>
</div>
@endsection
