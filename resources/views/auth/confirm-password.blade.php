{{-- resources/views/auth/confirm-password.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('label_common_ConfirmPassword', 'Confirm Password'))

@section('content')
    <div class="max-w-lg mx-auto w-full">
        <x-tab type="accordion"
               :title="lexicon('label_auth_SecureArea', 'Secure Area')"
               icon="bi-shield-lock"
               :open="true">
            <div class="flex flex-col gap-md">
                <span class="text-sm text-muted block">
                    {{ lexicon('msg_auth_ConfirmPasswordPrompt', 'Please confirm your password before continuing.') }}
                </span>

                <form method="POST"
                      action="{{ route('password.confirm') }}"
                      x-data="ajaxForm"
                      @submit.prevent="submit"
                      class="flex flex-col gap-md">
                    @csrf
                    <x-metadata name="password"
                                type="password"
                                :label="lexicon('label_common_Password', 'Password')"
                                :required="true"
                                autocomplete="current-password" />

                    <div class="border-t-actions pt-4 text-right flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="submit"
                                  variant="primary"
                                  :tooltip="lexicon('tooltip_auth_ConfirmPassword', 'Confirm your password to proceed')">
                            {{ lexicon('btn_common_Confirm', 'Confirm') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </x-tab>
    </div>
@endsection