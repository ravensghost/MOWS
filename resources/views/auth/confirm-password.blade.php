@extends('layouts.app')

@section('title', lexicon('label_common_ConfirmPassword', 'Confirm Password'))

@section('content')
<div x-data>
    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon('label_common_ConfirmPassword', 'Confirm Password')" />

    <div class="max-w-lg mx-auto w-full">
        <x-accordion-panel :title="lexicon('label_auth_SecureArea', 'Secure Area')" :expanded="true">
            <div class="flex flex-col gap-md">
                <span class="text-sm text-muted block">
                    {{ lexicon('msg_auth_ConfirmPasswordPrompt', 'Please confirm your password before continuing.') }}
                </span>

                <form method="POST" action="{{ route('password.confirm') }}" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                    @csrf
                    <x-form-input name="password"
                                  type="password"
                                  :label="lexicon('label_common_Password', 'Password')"
                                  :required="true"
                                  autocomplete="current-password" />

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="submit"
                                  variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_auth_ConfirmPassword', 'Confirm your password to proceed')) }}'">
                            {{ lexicon('btn_common_Confirm', 'Confirm') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </x-accordion-panel>
    </div>
</div>
@endsection