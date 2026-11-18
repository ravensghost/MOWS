<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $redirectUrl = route('dashboard', absolute: false);

            if ($request->expectsJson()) {
                return response()->json([
                    'status'   => 'success',
                    'message'  => lexicon('msg_auth_EmailAlreadyVerified', 'Your email address is already verified.'),
                    'data'     => null,
                    'redirect' => $redirectUrl,
                ], 200);
            }

            return redirect()->intended($redirectUrl);
        }

        return view('auth.verify-email');
    }
}