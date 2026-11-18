<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse|JsonResponse
    {
        $redirectUrl = route('dashboard', absolute: false) . '?verified=1';

        if ($request->user()->hasVerifiedEmail()) {
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

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => lexicon('msg_auth_EmailVerified', 'Your email address has been verified.'),
                'data'     => null,
                'redirect' => $redirectUrl,
            ], 200);
        }

        return redirect()->intended($redirectUrl);
    }
}