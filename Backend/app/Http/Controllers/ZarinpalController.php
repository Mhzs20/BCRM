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

        $invoice = new Invoice;
        $invoice->amount($smsPackage->price);
        $invoice->detail('description', 'خرید بسته پیامکی');
        $invoice->detail('user_id', $user->id);
        $invoice->detail('package_id', $smsPackage->id);
        $invoice->detail('salon_id', $activeSalon->id);


        $payment = Payment::purchase($invoice, function($driver, $transactionId) use ($user, $smsPackage, $activeSalon) {
            SmsTransaction::create([
                'user_id' => $user->id,
                'salon_id' => $activeSalon->id,
                'sms_package_id' => $smsPackage->id,
                'amount' => $smsPackage->price,
                'transaction_id' => $transactionId,
                'status' => 'pending',
            ]);
        })->pay();

        return response()->json([
            'payment_url' => $payment->getTargetUrl(),
        ]);
    }

    public function callback(Request $request)
    {
        $transaction = SmsTransaction::where('transaction_id', $request->client_ref_id)->firstOrFail();

        try {
            $receipt = Payment::amount($transaction->amount)->transactionId($request->client_ref_id)->verify();

            $transaction->update(['status' => 'completed']);

            $userSmsBalance = UserSmsBalance::firstOrCreate(['user_id' => $transaction->user_id]);
            $smsPackage = SmsPackage::find($transaction->sms_package_id);
            $userSmsBalance->increment('balance', $smsPackage->sms_count);

            return redirect('/')->with('success', 'پرداخت با موفقیت انجام شد.');

        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            return redirect('/')->with('error', 'پرداخت ناموفق بود.');
        }
    }
}
