{{-- resources/views/auth/reset-password.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('label_auth_ResetPassword', 'Reset Password'))

@section('content')
    <div class="max-w-lg mx-auto w-full">
        <x-tab type="accordion"
               :title="lexicon('label_auth_ResetPassword', 'Reset Password')"
               icon="bi-key"
               :open="true">
            <form method="POST"
                  action="{{ route('password.store') }}"
                  x-data="ajaxForm"
                  @submit.prevent="submit"
                  class="flex flex-col gap-md">
                @csrf
                <x-metadata type="hidden" name="token" :value="$request->route('token')" />

                <x-metadata name="email"
                            type="email"
                            :label="lexicon('label_common_EmailAddress', 'Email Address')"
                            :value="old('email', $request->email)"
                            :required="true"
                            autocomplete="username" />

                <x-metadata name="password"
                            type="password"
                            :label="lexicon('label_common_NewPassword', 'New Password')"
                            :required="true"
                            autocomplete="new-password" />

                <x-metadata name="password_confirmation"
                            type="password"
                            :label="lexicon('label_common_ConfirmPassword', 'Confirm Password')"
                            :required="true"
                            autocomplete="new-password" />

                <div class="border-t-actions pt-4 text-right flex flex-row flex-wrap items-center justify-end gap-sm">
                    <x-button type="submit"
                              variant="primary"
                              :tooltip="lexicon('tooltip_auth_SubmitResetPassword', 'Save your new password')">
                        {{ lexicon('btn_auth_ResetPassword', 'Reset Password') }}
                    </x-button>
                </div>
            </form>
        </x-tab>
    </div>
@endsection