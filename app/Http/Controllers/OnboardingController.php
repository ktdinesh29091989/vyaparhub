<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        // Nothing left to do here once they already have a product — send them on to the dashboard.
        if ($request->user()->products()->exists()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding');
    }
}
