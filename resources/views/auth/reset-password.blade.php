@extends('layouts.app')

@section('title', lexicon('label_auth_ResetPassword', 'Reset Password'))

@section('content')
<div x-data>
    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon('label_auth_ResetPassword', 'Reset Password')" :showBack="false" />

    <div class="max-w-lg mx-auto w-full">
        <x-accordion-panel :title="lexicon('label_auth_ResetPassword', 'Reset Password')" :expanded="true">
            <form method="POST" action="{{ route('password.store') }}" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <x-form-input name="email"
                              type="email"
                              :label="lexicon('label_common_EmailAddress', 'Email Address')"
                              :value="old('email', $request->email)"
                              :required="true"
                              autocomplete="username" />

                <x-form-input name="password"
                              type="password"
                              :label="lexicon('label_common_NewPassword', 'New Password')"
                              :required="true"
                              autocomplete="new-password" />

                <x-form-input name="password_confirmation"
                              type="password"
                              :label="lexicon('label_common_ConfirmPassword', 'Confirm Password')"
                              :required="true"
                              autocomplete="new-password" />

                <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                    <x-button type="submit"
                              variant="primary"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_SubmitResetPassword', 'Save your new password')) }}'">
                        {{ lexicon('btn_auth_ResetPassword', 'Reset Password') }}
                    </x-button>
                </div>
            </form>
        </x-accordion-panel>
    </div>
</div>
@endsection