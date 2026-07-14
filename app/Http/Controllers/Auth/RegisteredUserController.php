<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(Request $request)
    {
        if (in_array($request->query('intent'), ['pro', 'annual'], true)) {
            $request->session()->put('register_intent', $request->query('intent'));
        }

        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9][0-9]{9}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'business_name' => $validated['business_name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->ensureDefaultChannels();

        event(new Registered($user));

        Auth::login($user);

        $intent = $request->session()->pull('register_intent');

        if (in_array($intent, ['pro', 'annual'], true)) {
            return redirect()->route('upgrade', ['plan' => $intent === 'annual' ? 'annual' : 'monthly']);
        }

        return redirect()->route('onboarding');
    }
}
