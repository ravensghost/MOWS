@extends('layouts.app')

@section('title', lexicon('label_auth_VerifyEmail', 'Verify Your Email'))

@section('content')
<div x-data>
    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon('label_auth_VerifyEmail', 'Verify Your Email')" />

    <div class="max-w-lg mx-auto w-full">
        <x-accordion-panel :title="lexicon('label_auth_VerifyEmail', 'Verify Your Email')" :expanded="true">
            <div class="flex flex-col gap-md">
                <span class="text-sm text-muted block">
                    {{ lexicon('msg_auth_VerificationPrompt', 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?') }}
                </span>

                @if (session('status') == 'verification-link-sent')
                    <div x-data="sessionAlert('{{ addslashes(lexicon('msg_auth_VerificationLinkSent', 'A new verification link has been sent to your email address.')) }}')"></div>
                @endif

                <div class="border-t-actions pt-6 flex flex-row flex-wrap gap-md items-center justify-between">
                    <form method="POST" action="{{ route('verification.send') }}" x-data="ajaxForm" @submit.prevent="submit">
                        @csrf
                        <x-button type="submit"
                                  variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_auth_ResendVerificationEmail', 'Send a fresh verification link to your email')) }}'">
                            {{ lexicon('btn_auth_ResendVerificationEmail', 'Resend Verification Email') }}
                        </x-button>
                    </form>

                    <form method="POST" action="{{ url('/logout') }}" x-data="ajaxForm" @submit.prevent="submit">
                        @csrf
                        <x-button type="submit"
                                  variant="text"
                                  class="text-sm text-muted hover-main"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_auth_Logout', 'Sign out of your account')) }}'">
                            {{ lexicon('btn_common_Logout', 'Logout') }}
                        </x-button>
                    </form>
                </div>
            </div>
        </x-accordion-panel>
    </div>
</div>
@endsection