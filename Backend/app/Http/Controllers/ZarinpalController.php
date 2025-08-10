<?php

namespace App\Http\Controllers;

use App\Models\SmsPackage;
use App\Models\UserSmsBalance;
use Illuminate\Http\Request;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use App\Models\SmsTransaction;
use Illuminate\Support\Facades\Auth;

class ZarinpalController extends Controller
{
    public function purchase(Request $request, $packageId)
    {
        $smsPackage = SmsPackage::findOrFail($packageId);
        $user = Auth::user();
        $activeSalon = $user->activeSalon;

        if (!$activeSalon) {
            // Or handle this case as you see fit, maybe return an error response
            throw new \Exception('No active salon found for the user.');
        }

        $finalPrice = $smsPackage->discount_price ?? $smsPackage->price;

        $invoice = new Invoice;
        $invoice->amount($finalPrice);
        $invoice->detail('description', 'خرید بسته پیامکی');
        $invoice->detail('user_id', $user->id);
        $invoice->detail('package_id', $smsPackage->id);
        $invoice->detail('salon_id', $activeSalon->id);


        $payment = Payment::purchase($invoice, function($driver, $transactionId) use ($user, $smsPackage, $activeSalon) {
            SmsTransaction::create([
                'user_id' => $user->id,
                'salon_id' => $activeSalon->id,
                'sms_package_id' => $smsPackage->id,
                'amount' => $finalPrice,
                'transaction_id' => $transactionId,
                'status' => 'pending',
            ]);
        })->pay();

        return response()->json([
            'payment_url' => $payment->getAction(),
        ]);
    }

    public function callback(Request $request)
    {
        $transaction = SmsTransaction::where('transaction_id', $request->client_ref_id)->firstOrFail();

        try {
            $receipt = Payment::amount($transaction->amount)->transactionId($request->client_ref_id)->verify();

            $transaction->update(['status' => 'completed']);

            // Find the salon associated with the transaction
            $salon = \App\Models\Salon::findOrFail($transaction->salon_id);

            // Find the purchased SMS package
            $smsPackage = SmsPackage::findOrFail($transaction->sms_package_id);

            // Find or create the user's SMS balance and increment it
            $userSmsBalance = UserSmsBalance::firstOrCreate(
                ['user_id' => $transaction->user_id],
                ['balance' => 0]
            );
            $userSmsBalance->increment('balance', $smsPackage->sms_count);

            return redirect('/')->with('success', 'پرداخت با موفقیت انجام شد.');
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            return redirect('/')->with('error', 'پرداخت ناموفق بود.');
        }
    }
}
