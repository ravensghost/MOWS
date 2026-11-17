{{-- resources/views/auth/verify-email.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('label_auth_VerifyEmail', 'Verify Your Email'))

@section('content')
    <div class="max-w-lg mx-auto w-full">
        <x-tab type="accordion"
               :title="lexicon('label_auth_VerifyEmail', 'Verify Your Email')"
               icon="bi-envelope-check"
               :open="true">
            <div class="flex flex-col gap-md">
                <span class="text-sm text-muted block">
                    {{ lexicon('msg_auth_VerificationPrompt', 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?') }}
                </span>

                @if(session('status') === 'verification-link-sent')
                    <div x-data
                         x-init="$nextTick(() => { if (window.Swal && typeof window.Swal.toast === 'function') { window.Swal.toast('success', '{{ addslashes(lexicon('msg_auth_VerificationLinkSent', 'A new verification link has been sent to your email address.')) }}'); } })"></div>
                @endif

                <div class="border-t-actions pt-4 flex flex-row flex-wrap gap-md items-center justify-between">
                    <form method="POST"
                          action="{{ route('verification.send') }}"
                          x-data="ajaxForm"
                          @submit.prevent="submit">
                        @csrf
                        <x-button type="submit"
                                  variant="primary"
                                  :tooltip="lexicon('tooltip_auth_ResendVerificationEmail', 'Send a fresh verification link to your email')">
                            {{ lexicon('btn_auth_ResendVerificationEmail', 'Resend Verification Email') }}
                        </x-button>
                    </form>

                    <form method="POST"
                          action="{{ url('/logout') }}"
                          x-data="ajaxForm"
                          @submit.prevent="submit">
                        @csrf
                        <x-button type="submit"
                                  variant="ghost"
                                  size="sm"
                                  :tooltip="lexicon('tooltip_auth_Logout', 'Sign out of your account')">
                            {{ lexicon('btn_common_Logout', 'Logout') }}
                        </x-button>
                    </form>
                </div>
            </div>
        </x-tab>
    </div>
@endsection