<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $redirectUrl = route('dashboard', absolute: false);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => lexicon('msg_auth_EmailAlreadyVerified', 'Your email address is already verified.'),
                    'redirect' => $redirectUrl,
                ]);
            }

            return redirect()->intended($redirectUrl);
        }

        $request->user()->sendEmailVerificationNotification();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => lexicon('msg_auth_VerificationLinkSent', 'A new verification link has been sent to your email address.'),
            ]);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
