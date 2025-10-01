@extends('admin.layouts.app')

@section('title', 'مدیریت آپشن‌ها')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            مدیریت آپشن‌های پکیج‌ها
        </h2>
    </div>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <p class="text-sm text-gray-600">
                    <i class="ri-information-line text-indigo-600"></i>
                    این آپشن‌ها از پیش تعریف شده‌اند و برای تغیر باید با تیم توسعه ارتباط بگیرید.
                </p>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($options as $option)
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <i class="ri-checkbox-circle-line text-indigo-600 text-xl ml-3"></i>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $option->name }}</h3>
                                </div>
                                <p class="text-sm text-gray-600 mr-9">{{ $option->details }}</p>
                            </div>
                            
                            <div class="mr-4 flex items-center">
                                <!-- Toggle Switch (RTL) -->
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                           class="sr-only peer option-toggle" 
                                           data-option-id="{{ $option->id }}"
                                           {{ $option->is_active ? 'checked' : '' }}>
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-[-100%] rtl:peer-checked:after:translate-x-[100%] peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="mr-3 text-sm font-medium text-gray-700">
                                        {{ $option->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        <i class="ri-inbox-line text-4xl text-gray-300"></i>
                        <p class="mt-2">هیچ آپشنی یافت نشد.</p>
                    </div>
                @endforelse
            </div>

            <div class="px-6 py-4 bg-gray-50">
                {{ $options->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.option-toggle');
        
        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const optionId = this.getAttribute('data-option-id');
                const isChecked = this.checked;
                
                fetch(`/admin/options/${optionId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ is_active: isChecked })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update label text
                        const label = this.parentElement.querySelector('span');
                        label.textContent = data.is_active ? 'فعال' : 'غیرفعال';
                        
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded shadow-lg z-50';
                        alertDiv.innerHTML = '<i class="ri-checkbox-circle-line ml-2"></i>' + data.message;
                        document.body.appendChild(alertDiv);
                        
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.checked = !isChecked; // Revert toggle on error
                    alert('خطا در تغییر وضعیت');
                });
            });
        });
    });
</script>
@endpush
