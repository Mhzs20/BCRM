@extends('admin.layouts.app')

@section('title', 'داشبورد')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        داشبورد
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- User Stats -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 col-span-1 md:col-span-2 lg:col-span-2">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <i class="ri-group-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">آمار کاربران (کل: {{ $totalUsers }})</h3>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div>
                            <canvas id="userStatusChart"></canvas>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center"><span class="flex items-center"><span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>فعال:</span><span class="font-semibold">{{ $activeUsers }}</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center"><span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>غیرفعال:</span><span class="font-semibold">{{ $inactiveUsers }}</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center"><span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>در انتظار:</span><span class="font-semibold">{{ $pendingUsers }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Appointments Today -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 col-span-1 md:col-span-2 lg:col-span-2">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <i class="ri-calendar-check-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">رزروهای امروز (کل: {{ $totalAppointmentsToday }})</h3>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div>
                            <canvas id="appointmentStatusChart"></canvas>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center"><span class="flex items-center"><span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>تکمیل شده:</span><span class="font-semibold">{{ $completedAppointmentsToday }}</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center"><span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>لغو شده:</span><span class="font-semibold">{{ $cancelledAppointmentsToday }}</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center"><span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>در انتظار:</span><span class="font-semibold">{{ $pendingAppointmentsToday }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Salons and Clinics -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 flex flex-col">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <i class="ri-store-2-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">سالن‌ها و کلینیک‌ها</h3>
                        </div>
                    </div>
                    <div class="mt-4 flex-grow flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-gray-600">تعداد کل</p>
                            <p class="font-bold text-4xl">{{ $totalSalons }}</p>
                        </div>
                    </div>
                </div>

                <!-- SMS Stats -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 flex flex-col">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <i class="ri-message-3-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">آمار پیامک</h3>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between items-center"><span class="text-gray-600">ارسال امروز:</span><span class="font-semibold">{{ $smsSentToday }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">ارسال این ماه:</span><span class="font-semibold">{{ $smsSentThisMonth }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">موجودی کل:</span><span class="font-bold text-lg">{{ $totalSmsBalance }}</span></div>
                    </div>
                </div>

                <!-- Income Stats -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 flex flex-col col-span-1 lg:col-span-2">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                            <i class="ri-money-dollar-circle-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">درآمد (تومان)</h3>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between items-center"><span class="text-gray-600">روزانه:</span><span class="font-semibold">{{ number_format($dailyIncome, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">ماهانه:</span><span class="font-semibold">{{ number_format($monthlyIncome, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">سالانه:</span><span class="font-semibold">{{ number_format($yearlyIncome, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">مجموع:</span><span class="font-bold text-lg">{{ number_format($totalIncome, 0) }}</span></div>
                    </div>
                </div>

                <!-- SMS Profitability Stats -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 flex flex-col col-span-1 lg:col-span-2">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-teal-500 rounded-md p-3">
                            <i class="ri-line-chart-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">سودآوری پیامک (تومان)</h3>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between items-center"><span class="text-gray-600">تعداد کل پارت فروخته شده:</span><span class="font-semibold">{{ number_format($totalSmsPartsSold, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">هزینه کل خرید پیامک:</span><span class="font-semibold">{{ number_format($totalSmsCost, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">درآمد کل از فروش پیامک:</span><span class="font-semibold">{{ number_format($totalSmsIncome, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">میانگین قیمت فروش هر پارت:</span><span class="font-semibold">{{ number_format($averageSmsSellingPrice, 2) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">سود خالص:</span><span class="font-bold text-lg">{{ number_format($netSmsProfit, 0) }}</span></div>
                        <div class="flex justify-between items-center"><span class="text-gray-600">درصد سود:</span><span class="font-bold text-lg">{{ number_format($smsProfitPercentage, 2) }}%</span></div>
                    </div>
                </div>

                <!-- Discount Codes Stats -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 col-span-1 md:col-span-2 lg:col-span-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <i class="ri-coupon-line text-2xl text-white"></i>
                            </div>
                            <div class="mr-4">
                                <h3 class="text-lg font-semibold text-gray-700">آمار کدهای تخفیف</h3>
                            </div>
                        </div>
                        <a href="{{ route('admin.discount-codes.index') }}" 
                           class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                            مشاهده همه
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $totalDiscountCodes }}</div>
                            <div class="text-sm text-blue-600">کل کدها</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $activeDiscountCodes }}</div>
                            <div class="text-sm text-green-600">فعال</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $expiredDiscountCodes }}</div>
                            <div class="text-sm text-red-600">منقضی شده</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ $usedDiscountCodes }}</div>
                            <div class="text-sm text-yellow-600">استفاده شده</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Usage Stats -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-3">آمار استفاده</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">تعداد استفاده:</span>
                                    <span class="font-semibold">{{ $totalDiscountUsage }} بار</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">مجموع تخفیف داده شده:</span>
                                    <span class="font-semibold">{{ number_format($totalDiscountAmount, 0) }} تومان</span>
                                </div>
                            </div>
                        </div>

                        <!-- Top Discount Codes -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-3">پرکاربردترین کدها</h4>
                            <div class="space-y-2">
                                @forelse($topDiscountCodes as $code)
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">{{ $code->code }}</span>
                                        <span class="text-gray-600">{{ $code->orders_count }} استفاده</span>
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-sm">هیچ کد تخفیفی استفاده نشده</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Salons -->
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 col-span-1 md:col-span-2 lg:col-span-4">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <i class="ri-trophy-line text-2xl text-white"></i>
                        </div>
                        <div class="mr-4">
                            <h3 class="text-lg font-semibold text-gray-700">۱۰ سالن برتر (بر اساس مصرف پیامک)</h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام سالن</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نام سالن‌دار</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شماره تماس</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مصرف پیامک</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($topSalons as $salon)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $salon->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $salon->user->name ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $salon->user->mobile ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="font-semibold bg-gray-200 text-gray-700 px-2 py-1 rounded-full text-sm">{{ $salon->sms_transactions_count }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 relative" style="height: 400px;">
                    <h3 class="text-lg font-semibold">نرخ رشد کاربران</h3>
                    <canvas id="userGrowthChart"></canvas>
                </div>
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 relative" style="height: 400px;">
                    <h3 class="text-lg font-semibold">نمودار فروش</h3>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 relative" style="height: 400px;">
                    <h3 class="text-lg font-semibold">گراف سود پیامک در بازه‌های زمانی</h3>
                    <canvas id="smsProfitChart"></canvas>
                </div>
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 relative" style="height: 400px;">
                    <h3 class="text-lg font-semibold">آمار کلی سودآوری پیامک</h3>
                    <canvas id="overallSmsProfitChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // User Status Doughnut Chart
            const userStatusCtx = document.getElementById('userStatusChart').getContext('2d');
            new Chart(userStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['فعال', 'غیرفعال', 'در انتظار'],
                datasets: [{
                    data: [{{ $activeUsers }}, {{ $inactiveUsers }}, {{ $pendingUsers }}],
                    backgroundColor: ['#10B981', '#EF4444', '#F59E0B'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Appointment Status Doughnut Chart
        const appointmentStatusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
        new Chart(appointmentStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['تکمیل شده', 'لغو شده', 'در انتظار'],
                datasets: [{
                    data: [{{ $completedAppointmentsToday }}, {{ $cancelledAppointmentsToday }}, {{ $pendingAppointmentsToday }}],
                    backgroundColor: ['#10B981', '#EF4444', '#F59E0B'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: @json(array_column($userGrowthData, 'month')),
                datasets: [{
                    label: 'رشد کاربران',
                    data: @json(array_column($userGrowthData, 'count')),
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            }
        });

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: @json(array_column($salesData, 'month')),
                datasets: [{
                    label: 'فروش',
                    data: @json(array_column($salesData, 'sum')),
                    backgroundColor: 'rgba(139, 92, 246, 0.5)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 1
                }]
            }
        });

        // SMS Profit Chart
        const smsProfitCtx = document.getElementById('smsProfitChart').getContext('2d');
        new Chart(smsProfitCtx, {
            type: 'line',
            data: {
                labels: @json(array_column($smsProfitData, 'month')),
                datasets: [{
                    label: 'سود خالص پیامک',
                    data: @json(array_column($smsProfitData, 'profit')),
                    borderColor: 'rgba(20, 184, 166, 1)',
                    backgroundColor: 'rgba(20, 184, 166, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'ماه'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'سود (تومان)'
                        }
                    }
                }
            }
        });

        // Overall SMS Profitability Chart (Bar Chart)
        const overallSmsProfitCtx = document.getElementById('overallSmsProfitChart').getContext('2d');
        new Chart(overallSmsProfitCtx, {
            type: 'bar',
            data: {
                labels: ['درآمد کل', 'هزینه کل', 'سود خالص'],
                datasets: [{
                    label: 'مبالغ (تومان)',
                    data: [{{ $totalSmsIncome }}, {{ $totalSmsCost }}, {{ $netSmsProfit }}],
                    backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)'],
                    borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('fa-IR', { style: 'currency', currency: 'IRR' }).format(context.parsed.y).replace('ریال', 'تومان');
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'آیتم'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'مبلغ (تومان)'
                        },
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('fa-IR').format(value);
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
@endsection
