<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletPackage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $packages = WalletPackage::orderBy('sort_order', 'asc')
                                ->orderBy('created_at', 'desc')
                                ->get();

        // Statistics
        $totalPackages = WalletPackage::count();
        $activePackages = WalletPackage::active()->count();
        $totalSales = Order::where('order_type', 'wallet_package')
                          ->where('status', 'completed')
                          ->count();
        $totalRevenue = Order::where('order_type', 'wallet_package')
                            ->where('status', 'completed')
                            ->sum('amount');

        return view('admin.wallet.packages.index', compact(
            'packages',
            'totalPackages',
            'activePackages',
            'totalSales',
            'totalRevenue'
        ));
    }

    /**
     * Show the form for creating a new package
     */
    public function create()
    {
        return view('admin.wallet.packages.create');
    }

    /**
     * Store a newly created package
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:1000',
            'price' => 'required|numeric|min:1000',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ], [
            'title.required' => 'عنوان پکیج الزامی است.',
            'amount.required' => 'مبلغ شارژ الزامی است.',
            'amount.min' => 'مبلغ شارژ باید حداقل 1000 تومان باشد.',
            'price.required' => 'قیمت پکیج الزامی است.',
            'price.min' => 'قیمت پکیج باید حداقل 1000 تومان باشد.',
            'discount_percentage.max' => 'درصد تخفیف نمی‌تواند بیش از 100 باشد.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            WalletPackage::create([
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount * 10, // Convert Toman to Rial
                'price' => $request->price * 10, // Convert Toman to Rial
                'discount_percentage' => $request->discount_percentage ?: 0,
                'is_active' => $request->boolean('is_active'),
                'is_featured' => false, // Default value
                'sort_order' => 0, // Default value
                'icon' => 'ri-wallet-line', // Default icon
                'color' => '#3B82F6', // Default color
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'پکیج شارژ کیف پول با موفقیت ایجاد شد.'
                ]);
            }

            return redirect()
                ->route('admin.wallet.packages.index')
                ->with('success', 'پکیج شارژ کیف پول با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در ایجاد پکیج: ' . $e->getMessage()
                ], 500);
            }
            return back()
                ->with('error', 'خطا در ایجاد پکیج: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(WalletPackage $package)
    {
        return response()->json($package);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WalletPackage $package)
    {
        return view('admin.wallet.packages.edit', compact('package'));
    }

    /**
     * Update the specified package
     */
    public function update(Request $request, WalletPackage $package)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:1000',
            'price' => 'required|numeric|min:1000',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
        ], [
            'title.required' => 'عنوان پکیج الزامی است.',
            'amount.required' => 'مبلغ شارژ الزامی است.',
            'amount.min' => 'مبلغ شارژ باید حداقل 1000 تومان باشد.',
            'price.required' => 'قیمت پکیج الزامی است.',
            'price.min' => 'قیمت پکیج باید حداقل 1000 تومان باشد.',
            'discount_percentage.max' => 'درصد تخفیف نمی‌تواند بیش از 100 باشد.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $package->update([
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount * 10, // Convert Toman to Rial
                'price' => $request->price * 10, // Convert Toman to Rial
                'discount_percentage' => $request->discount_percentage ?: 0,
                'is_active' => $request->boolean('is_active'),
                // Keep existing values for fields not in the form
                'is_featured' => $package->is_featured,
                'sort_order' => $package->sort_order,
                'icon' => $package->icon,
                'color' => $package->color,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'پکیج شارژ کیف پول با موفقیت بروزرسانی شد.'
                ]);
            }

            return redirect()
                ->route('admin.wallet.packages.index')
                ->with('success', 'پکیج شارژ کیف پول با موفقیت بروزرسانی شد.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در بروزرسانی پکیج: ' . $e->getMessage()
                ], 500);
            }
            return back()
                ->with('error', 'خطا در بروزرسانی پکیج: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified package
     */
    public function destroy(WalletPackage $package)
    {
        try {
            $package->delete();

            return redirect()
                ->route('admin.wallet.packages.index')
                ->with('success', 'پکیج شارژ کیف پول با موفقیت حذف شد.');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'خطا در حذف پکیج: ' . $e->getMessage());
        }
    }

    /**
     * Toggle package status
     */
    public function toggleStatus(WalletPackage $package)
    {
        try {
            $package->update([
                'is_active' => !$package->is_active
            ]);

            $status = $package->is_active ? 'فعال' : 'غیرفعال';
            
            return response()->json([
                'success' => true,
                'message' => "وضعیت پکیج به '{$status}' تغییر یافت.",
                'is_active' => $package->is_active
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تغییر وضعیت: ' . $e->getMessage()
            ], 500);
        }
    }
}
