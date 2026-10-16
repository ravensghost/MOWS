<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => lexicon('msg_auth_IncorrectPassword', 'The provided password was incorrect.'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        $intendedUrl = redirect()->intended(route('dashboard', absolute: false))->getTargetUrl();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => lexicon('msg_auth_PasswordConfirmed', 'Password confirmed.'),
                'redirect' => $intendedUrl,
            ]);
        }

        return redirect()->to($intendedUrl);
    }
}
