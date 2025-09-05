<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center px-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">ویرایش سالن: {{ $salon->name }}</h3>
            <button type="button" onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-full p-1">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="editForm" action="{{ route('admin.salons.update', $salon->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="max-h-[70vh] overflow-y-auto pr-2"> {{-- Adjusted max-height for better modal sizing --}}
                <h2 class="text-xl font-semibold text-gray-800 mb-3 border-b-2 border-indigo-500 pb-2">اطلاعات عمومی سالن</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">نام سالن</label>
                        <input type="text" name="name" id="edit_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('name', $salon->name) }}" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_mobile" class="block text-sm font-medium text-gray-700 mb-2">شماره تماس سالن</label>
                        <input type="text" name="mobile" id="edit_mobile" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('mobile', $salon->mobile) }}" required>
                        @error('mobile') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل سالن</label>
                        <input type="email" name="email" id="edit_email" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('email', $salon->email ?? '') }}">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_business_category_id" class="block text-sm font-medium text-gray-700 mb-2">نوع فعالیت</label>
                        <select name="business_category_id" id="edit_business_category_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <option value="">انتخاب کنید</option>
                            @foreach($businessCategories as $category)
                                <option value="{{ $category->id }}" @if(old('business_category_id', $salon->business_category_id) == $category->id) selected @endif>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('business_category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_business_subcategory_id" class="block text-sm font-medium text-gray-700 mb-2">زیرمجموعه فعالیت</label>
                        <select name="business_subcategory_ids[]" id="edit_business_subcategory_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" multiple>
                            <option value="">انتخاب کنید</option>
                            @foreach($businessSubcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" @if(in_array($subcategory->id, old('business_subcategory_ids', $salon->businessSubcategories->pluck('id')->toArray()))) selected @endif>{{ $subcategory->name }}</option>
                            @endforeach
                        </select>
                        @error('business_subcategory_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mb-3 border-b-2 border-indigo-500 pb-2">اطلاعات تماس و شبکه‌های اجتماعی</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="edit_whatsapp" class="block text-sm font-medium text-gray-700 mb-2">واتساپ</label>
                        <input type="text" name="whatsapp" id="edit_whatsapp" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('whatsapp', $salon->whatsapp ?? '') }}">
                        @error('whatsapp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_telegram" class="block text-sm font-medium text-gray-700 mb-2">تلگرام</label>
                        <input type="text" name="telegram" id="edit_telegram" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('telegram', $salon->telegram ?? '') }}">
                        @error('telegram') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_instagram" class="block text-sm font-medium text-gray-700 mb-2">اینستاگرام</label>
                        <input type="text" name="instagram" id="edit_instagram" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('instagram', $salon->instagram ?? '') }}">
                        @error('instagram') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_website" class="block text-sm font-medium text-gray-700 mb-2">وبسایت</label>
                        <input type="text" name="website" id="edit_website" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('website', $salon->website ?? '') }}">
                        @error('website') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_support_phone_number" class="block text-sm font-medium text-gray-700 mb-2">شماره تماس پشتیبانی</label>
                        <input type="text" name="support_phone_number" id="edit_support_phone_number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('support_phone_number', $salon->support_phone_number ?? '') }}">
                        @error('support_phone_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="edit_bio" class="block text-sm font-medium text-gray-700 mb-2">بیوگرافی سالن</label>
                        <textarea name="bio" id="edit_bio" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('bio', $salon->bio ?? '') }}</textarea>
                        @error('bio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mb-3 border-b-2 border-indigo-500 pb-2">اطلاعات مالک سالن</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="edit_owner_name" class="block text-sm font-medium text-gray-700 mb-2">نام مالک</label>
                        <input type="text" name="owner_name" id="edit_owner_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('owner_name', $salon->user->name ?? '') }}" required>
                        @error('owner_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_owner_email" class="block text-sm font-medium text-gray-700 mb-2">ایمیل مالک</label>
                        <input type="email" name="owner_email" id="edit_owner_email" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('owner_email', $salon->user->email ?? '') }}">
                        @error('owner_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 mb-3 border-b-2 border-indigo-500 pb-2">آدرس و لوکیشن</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="edit_province_id" class="block text-sm font-medium text-gray-700 mb-2">استان</label>
                        <select name="province_id" id="edit_province_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <option value="">انتخاب کنید</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" @if(old('province_id', $salon->province_id) == $province->id) selected @endif>{{ $province->name }}</option>
                            @endforeach
                        </select>
                        @error('province_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit_city_id" class="block text-sm font-medium text-gray-700 mb-2">شهر</label>
                        <select name="city_id" id="edit_city_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <option value="">انتخاب کنید</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @if(old('city_id', $salon->city_id) == $city->id) selected @endif>{{ $city->name }}</option>
                            @endforeach
                        </select>
                        @error('city_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="edit_address" class="block text-sm font-medium text-gray-700 mb-2">آدرس دقیق</label>
                        <textarea name="address" id="edit_address" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('address', $salon->address) }}</textarea>
                        @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">انتخاب موقعیت از روی نقشه</label>
                    <div id="mapid" class="h-64 w-full rounded-md border border-gray-300 shadow-sm"></div>
                    <input type="hidden" name="lat" id="edit_latitude" value="{{ old('lat', $salon->lat) }}">
                    <input type="hidden" name="lang" id="edit_longitude" value="{{ old('lang', $salon->lang) }}">
                    @error('lat') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @error('lang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            </div>

            <div class="flex justify-end mt-6 space-x-3 space-x-reverse">
                <button type="button" onclick="closeModal('editModal')" class="btn-secondary">
                    لغو
                </button>
                <button type="submit" class="btn-action bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500">
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var map = L.map('mapid').setView([35.6892, 51.3890], 13); // Default to Tehran

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var initialLat = document.getElementById('edit_latitude').value;
        var initialLang = document.getElementById('edit_longitude').value;
        var marker;

        if (initialLat && initialLang) {
            map.setView([initialLat, initialLang], 13);
            marker = L.marker([initialLat, initialLang], {draggable: true}).addTo(map);
        } else {
            marker = L.marker(map.getCenter(), {draggable: true}).addTo(map);
            document.getElementById('edit_latitude').value = map.getCenter().lat;
            document.getElementById('edit_longitude').value = map.getCenter().lng;
        }

        marker.on('dragend', function (event) {
            var latlng = marker.getLatLng();
            document.getElementById('edit_latitude').value = latlng.lat;
            document.getElementById('edit_longitude').value = latlng.lng;
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('edit_latitude').value = e.latlng.lat;
            document.getElementById('edit_longitude').value = e.latlng.lng;
        });

        // Invalidate map size when modal is opened to ensure it renders correctly
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    var modal = document.getElementById('editModal');
                    if (!modal.classList.contains('hidden')) {
                        setTimeout(function() {
                            map.invalidateSize();
                        }, 100); // Small delay to ensure modal is fully visible
                    }
                }
            });
        });

        var modalElement = document.getElementById('editModal');
        if (modalElement) {
            observer.observe(modalElement, { attributes: true });
        }
    });
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<style>
    /* Adjust Leaflet z-index to prevent it from overlapping modal content */
    .leaflet-pane {
        z-index: 1 !important;
    }
    .leaflet-control-container {
        z-index: 2 !important;
    }
    .leaflet-marker-pane {
        z-index: 1 !important;
    }
    .leaflet-bottom{
        display: none !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const provinceSelect = document.getElementById('edit_province_id');
        const citySelect = document.getElementById('edit_city_id');
        const businessCategorySelect = document.getElementById('edit_business_category_id');
        const businessSubcategorySelect = document.getElementById('edit_business_subcategory_id');

        // Store initial values for pre-selection
        const initialCityId = "{{ old('city_id', $salon->city_id) }}";
        const initialSubcategoryIds = @json(old('business_subcategory_ids', $salon->businessSubcategories->pluck('id')->toArray()));

        // Function to fetch and populate cities
        async function fetchAndPopulateCities(provinceId, selectedCityId = null) {
            citySelect.innerHTML = '<option value="">انتخاب کنید</option>'; // Clear existing options
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

        // Function to fetch and populate subcategories
        async function fetchAndPopulateSubcategories(categoryId, selectedSubcategoryIds = []) {
            businessSubcategorySelect.innerHTML = '<option value="">انتخاب کنید</option>'; // Clear existing options
            if (!categoryId) {
                return;
            }
            try {
                const url = `/api/general/business-categories/${categoryId}/subcategories`;
                const response = await fetch(url);
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
                        if (selectedSubcategoryIds.includes(subcategory.id)) {
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

        // Event listener for province change
        provinceSelect.addEventListener('change', function () {
            fetchAndPopulateCities(this.value);
        });

        // Event listener for business category change
        businessCategorySelect.addEventListener('change', function () {
            fetchAndPopulateSubcategories(this.value);
        });

        // Initial load for cities if a province is already selected
        if (provinceSelect.value) {
            fetchAndPopulateCities(provinceSelect.value, initialCityId);
        }

        // Initial load for subcategories if a business category is already selected
        if (businessCategorySelect.value) {
            fetchAndPopulateSubcategories(businessCategorySelect.value, initialSubcategoryIds);
        }

        // Ensure the initial values are set correctly when the modal is opened
        // This is important if the modal content is dynamically loaded or re-rendered
        const editModal = document.getElementById('editModal');
        if (editModal) {
            editModal.addEventListener('modal:opened', function() {
                if (provinceSelect.value) {
                    fetchAndPopulateCities(provinceSelect.value, initialCityId);
                }
                if (businessCategorySelect.value) {
                    fetchAndPopulateSubcategories(businessCategorySelect.value, initialSubcategoryIds);
                }
            });
        }
    });

    // Helper function to open/close modal (assuming it exists elsewhere or needs to be defined)
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.getElementById(modalId).classList.add('flex');
        // Dispatch a custom event when modal is opened
        const event = new Event('modal:opened');
        document.getElementById(modalId).dispatchEvent(event);
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        document.getElementById(modalId).classList.remove('flex');
    }
</script>
