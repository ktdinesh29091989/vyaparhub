<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return view('profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'digits:10', 'regex:/^[6-9][0-9]{9}$/'],
        ], [
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('status', 'Password changed.');
    }
}
