<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'twitter_handle'  => ['nullable', 'string', 'max:255'],
            'facebook_handle' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $message = lexicon('msg_Dashboard_ProfileUpdated', 'Profile information updated successfully.');

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'redirect' => url('/'),
                'message'  => lexicon('msg_Dashboard_AccountDeleted', 'Your account has been permanently deleted.'),
            ]);
        }

        return redirect('/');
    }
}
