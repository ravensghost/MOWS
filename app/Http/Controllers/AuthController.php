<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Record login timestamp in user_attributes for Player Activity Auditing
            $user = auth()->user();
            if ($user && method_exists($user, 'setAttr')) {
                $user->setAttr('last_login_at', now()->toDateTimeString());
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status'   => 'success',
                    'success'  => true,
                    'message'  => lexicon('msg_auth_LoginSuccess', 'Authenticated successfully.'),
                    'data'     => $user,
                    'redirect' => '/dashboard',
                ], 200);
            }

            return redirect()->intended('/dashboard');
        }

        $errorMessage = lexicon('msg_auth_InvalidCredentials', 'The provided credentials do not match our records.');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'   => 'error',
                'success'  => false,
                'message'  => $errorMessage,
                'data'     => null,
                'redirect' => null,
                'errors'   => ['email' => [$errorMessage]],
            ], 422);
        }

        return back()->withErrors([
            'email' => $errorMessage,
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'   => 'success',
                'success'  => true,
                'message'  => lexicon('msg_auth_LogoutSuccess', 'You have been logged out.'),
                'data'     => null,
                'redirect' => '/',
            ], 200);
        }

        return redirect('/');
    }
}