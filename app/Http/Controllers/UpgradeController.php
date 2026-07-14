<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UpgradeController extends Controller
{
    public function index(Request $request)
    {
        $autoOpenPlan = $request->query('plan');
        $autoOpenPlan = in_array($autoOpenPlan, ['monthly', 'annual'], true) ? $autoOpenPlan : null;

        return view('upgrade', [
            'user' => $request->user(),
            'razorpayKey' => config('services.razorpay.key'),
            'autoOpenPlan' => $autoOpenPlan,
        ]);
    }
}
