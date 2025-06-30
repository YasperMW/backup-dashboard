<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\User;

class TwoFactorController extends Controller
{
    public function create(): View
    {
        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::where('two_factor_secret', $request->code)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => [trans('auth.failed')],
            ]);
        }

        // Clear the 2FA code
        $user->two_factor_secret = null;
        $user->save();

        // Log the user in
        Auth::login($user);

        // Get the intended URL from the session
        $intended = session()->get('url.intended', route('dashboard', absolute: false));
        session()->forget('url.intended');

        return redirect()->intended($intended);
    }
} 