@extends('admin.layouts.app')

@section('title', 'مدیریت سالن‌ها')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">مدیریت سالن‌ها</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-xl rounded-lg p-6 mb-8">
        <form action="{{ route('admin.salons.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-end">
            <div class="col-span-1">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">جستجو (نام، شماره تماس، مالک، شهر)</label>
                <input type="text" name="search" id="search" placeholder="جستجو..." class="form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('search') }}">
            </div>
            <div class="col-span-1">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">وضعیت</label>
                <select name="status" id="status" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                    <option value="">همه</option>
                    <option value="1" @if(request('status') === '1') selected @endif>فعال</option>
                    <option value="0" @if(request('status') === '0') selected @endif>غیرفعال</option>
                </select>
            </div>
            <div class="col-span-1">
                <label for="province_id" class="block text-sm font-medium text-gray-700 mb-1">استان</label>
                <select name="province_id" id="province_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                    <option value="">همه</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}" @if(request('province_id') == $province->id) selected @endif>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-1">
                <label for="city_id" class="block text-sm font-medium text-gray-700 mb-1">شهر</label>
                <select name="city_id" id="city_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                    <option value="">همه</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @if(request('city_id') == $city->id) selected @endif>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-1">
                <label for="business_category_id" class="block text-sm font-medium text-gray-700 mb-1">دسته بندی فعالیت</label>
                <select name="business_category_id" id="business_category_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                    <option value="">همه</option>
                    @foreach($businessCategories as $category)
                        <option value="{{ $category->id }}" @if(request('business_category_id') == $category->id) selected @endif>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-1">
                <label for="business_subcategory_id" class="block text-sm font-medium text-gray-700 mb-1">زیردسته بندی فعالیت</label>
                <select name="business_subcategory_id" id="business_subcategory_id" class="form-select w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out">
                    <option value="">همه</option>
                    @foreach($businessSubcategories as $subcategory)
                        <option value="{{ $subcategory->id }}" @if(request('business_subcategory_id') == $subcategory->id) selected @endif>{{ $subcategory->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-1">
                <label for="created_at_start" class="block text-sm font-medium text-gray-700 mb-1">تاریخ ثبت از</label>
                <input type="text" name="created_at_start" id="created_at_start" placeholder="برای مثال: 1402/01/01" class="jalali-datepicker form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('created_at_start') }}">
            </div>
            <div class="col-span-1">
                <label for="created_at_end" class="block text-sm font-medium text-gray-700 mb-1">تاریخ ثبت تا</label>
                <input type="text" name="created_at_end" id="created_at_end" placeholder="برای مثال: 1402/12/29" class="jalali-datepicker form-input w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 p-2.5 text-gray-900 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out" value="{{ request('created_at_end') }}">
            </div>
            <div class="flex flex-col sm:flex-row space-y-2 w-full sm:space-y-0 sm:space-x-4 col-span-full justify-between mt-4">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                    <i class="ri-search-line ml-2"></i> اعمال فیلتر
                </button>
                <a href="{{ route('admin.salons.index') }}" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                    <i class="ri-close-line ml-2"></i> پاک کردن فیلترها
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white shadow-xl rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        نام سالن
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        شماره تماس
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        مالک
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        شهر
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        وضعیت
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        عملیات
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($salons as $salon)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $salon->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $salon->owner->mobile ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $salon->owner->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $salon->city->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $salon->is_active ? 'bg-green-100 text-green-800' : 'bg-red-110 text-red-800' }}">
                                {{ $salon->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.salons.show', $salon->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="ri-eye-line ml-1"></i> مشاهده
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            هیچ سالنی یافت نشد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $salons->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Jalali date pickers
        $('.jalali-datepicker').each(function() {
            $(this).persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValueType: 'persian',
                onSelect: function(unix) {
                    var date = new persianDate(unix).format('YYYY/MM/DD');
                    this.el.val(date);
                }
            });
        });

        const provinceSelect = document.getElementById('province_id');
        const citySelect = document.getElementById('city_id');
        const businessCategorySelect = document.getElementById('business_category_id');
        const businessSubcategorySelect = document.getElementById('business_subcategory_id');

        const initialCityId = "{{ request('city_id') }}";
        const initialSubcategoryId = "{{ request('business_subcategory_id') }}";

        async function fetchAndPopulateCities(provinceId, selectedCityId = null) {
            citySelect.innerHTML = '<option value="">همه</option>'; // "All" option for filters
            if (!provinceId) {
                return;
            }
            try {
                const response = await fetch(`/api/general/provinces/${provinceId}/cities`);
                const cities = await response.json();
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    if (selectedCityId && city.id == selectedCityId) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error fetching cities:', error);
            }
        }

        async function fetchAndPopulateSubcategories(categoryId, selectedSubcategoryId = null) {
            businessSubcategorySelect.innerHTML = '<option value="">همه</option>'; // "All" option for filters
            if (!categoryId) {
                return;
            }
            try {
                const response = await fetch(`/api/general/business-categories/${categoryId}/subcategories`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const responseData = await response.json();
                const subcategories = responseData.data; // Access the 'data' property
                if (Array.isArray(subcategories)) {
                    subcategories.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        if (selectedSubcategoryId && subcategory.id == selectedSubcategoryId) {
                            option.selected = true;
                        }
                        businessSubcategorySelect.appendChild(option);
                    });
                } else {
                    console.error('Subcategories data is not an array:', subcategories);
                }
            } catch (error) {
                console.error('Error fetching subcategories:', error);
            }
        }

        provinceSelect.addEventListener('change', function () {
            fetchAndPopulateCities(this.value);
        });

        businessCategorySelect.addEventListener('change', function () {
            fetchAndPopulateSubcategories(this.value);
        });

        // Initial load for cities if a province is already selected
        if (provinceSelect.value) {
            fetchAndPopulateCities(provinceSelect.value, initialCityId);
        }

        // Initial load for subcategories if a business category is already selected
        if (businessCategorySelect.value) {
            fetchAndPopulateSubcategories(businessCategorySelect.value, initialSubcategoryId);
        }
    });
</script>
@endpush
