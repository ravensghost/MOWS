<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => __($status),
                ]);
            }

            return back()->with('status', __($status));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => __($status),
                'errors' => ['email' => [__($status)]],
            ], 422);
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}