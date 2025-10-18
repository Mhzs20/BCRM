<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReferralDashboardController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.referral.dashboard');
    }
}