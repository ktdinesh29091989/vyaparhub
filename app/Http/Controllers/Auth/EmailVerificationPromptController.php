<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class EmailVerificationPromptController extends Controller
{
    /** Show the "check your email" notice, or skip straight through if already verified. */
    public function __invoke(Request $request): RedirectResponse|\Illuminate\View\View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('onboarding')
            : view('auth.verify-email');
    }
}
