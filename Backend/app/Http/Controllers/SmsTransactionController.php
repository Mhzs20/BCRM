<?php

namespace App\Http\Controllers;

use App\Models\SmsTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class SmsTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $transactions = SmsTransaction::where('user_id', $user->id)
            ->whereNotNull('sms_package_id')
            ->with('smsPackage')
            ->latest()
            ->get();

        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'package_name' => $transaction->smsPackage->name,
                'purchase_date' => Jalalian::fromDateTime($transaction->created_at)->format('Y/m/d H:i:s'),
                'price' => $transaction->amount,
            ];
        });

        return response()->json($formattedTransactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SmsTransaction $smsTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SmsTransaction $smsTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SmsTransaction $smsTransaction)
    {
        //
    }
}
