{{-- resources/views/components/meta.blade.php --}}
@props([
    'type' => null,
    'action' => '',
    'xAction' => null,
    'method' => 'POST',
    'xMethod' => null,
    'ajax' => true,
    'loadingTitle' => null,
    'loadingMsg' => null,
    'form' => null,
    'fields' => null,
    'activeMode' => null,
    'request' => null,
    'footerClass' => null,
])

@php
    if ($type === null) {
        if ($form !== null ||$fields !== null || $activeMode !== null) {$type = 'dynamic';
        } else {
            $type = 'default';
        }
    }

    $upperMethod = strtoupper((string) $method);$needsSpoofing = !$xMethod && !in_array($upperMethod, ['GET', 'POST'], true);
    $htmlMethod =$upperMethod === 'GET' ? 'GET' : 'POST';
@endphp

@switch($type)
    @case('auth')
        <div class="flex flex-col gap-md w-full">
            <!-- 1. LOGIN FORM -->
            <form method="POST"
                  action="/login"
                  class="flex flex-col gap-md w-full"
                  x-show="view === 'login'"
                  x-data="ajaxForm"
                  @submit.prevent="submit">
                @csrf
                <x-metadata name="email"
                            type="email"
                            :label="lexicon('label_common_EmailAddress', 'Email Address')"
                            :required="true"
                            autocomplete="username" />

                <x-metadata name="password"
                            type="password"
                            :label="lexicon('label_common_Password', 'Password')"
                            :required="true"
                            autocomplete="current-password" />

                <x-metadata name="remember"
                            type="checkbox"
                            id="auth_remember_me"
                            :label="lexicon('label_auth_RememberMe', 'Remember me')" />

                <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap gap-md items-center justify-between">
                    <x-button type="button"
                              variant="ghost"
                              size="sm"
                              :tooltip="lexicon('tooltip_auth_ForgotPassword', 'Reset a forgotten password')"
                              @click="view = 'forgot'">
                        {{ lexicon('btn_auth_ForgotPassword', 'Forgot password?') }}
                    </x-button>
                    <div class="flex flex-row flex-wrap gap-sm items-center">
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_auth_SwitchToRegister', 'Switch to account registration')"
                                  @click="view = 'register'">
                            {{ lexicon('btn_common_Register', 'Register') }}
                        </x-button>
                        <x-button type="submit"
                                  variant="primary"
                                  :tooltip="lexicon('tooltip_auth_SubmitLogin', 'Sign in to your account')">
                            {{ lexicon('btn_common_LogIn', 'Log In') }}
                        </x-button>
                    </div>
                </div>
            </form>

            <!-- 2. REGISTER FORM -->
            <form method="POST"
                  action="/register"
                  class="flex flex-col gap-md w-full"
                  x-show="view === 'register'"
                  x-cloak
                  x-data="ajaxForm"
                  @submit.prevent="submit">
                @csrf
                <x-metadata name="name"
                            type="text"
                            :label="lexicon('label_common_FullName', 'Full Name')"
                            :required="true"
                            autocomplete="name" />

                <x-metadata name="email"
                            type="email"
                            :label="lexicon('label_common_EmailAddress', 'Email Address')"
                            :required="true"
                            autocomplete="username" />

                <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                    <x-metadata name="password"
                                type="password"
                                :label="lexicon('label_common_Password', 'Password')"
                                :required="true"
                                autocomplete="new-password" />

                    <x-metadata name="password_confirmation"
                                type="password"
                                :label="lexicon('label_common_Confirm', 'Confirm')"
                                :required="true"
                                autocomplete="new-password" />
                </div>

                <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap gap-md items-center justify-between">
                    <x-button type="button"
                              variant="ghost"
                              size="sm"
                              :tooltip="lexicon('tooltip_auth_BackToLogin', 'Return to login form')"
                              @click="view = 'login'">
                        &larr; {{ lexicon('btn_auth_BackToLogin', 'Back to Login') }}
                    </x-button>
                    <x-button type="submit"
                              variant="primary"
                              :tooltip="lexicon('tooltip_auth_SubmitRegister', 'Create your new account')">
                        {{ lexicon('btn_auth_CreateAccount', 'Create Account') }}
                    </x-button>
                </div>
            </form>

            <!-- 3. FORGOT PASSWORD FORM -->
            <form method="POST"
                  action="{{ route('password.email') }}"
                  class="flex flex-col gap-md w-full"
                  x-show="view === 'forgot'"
                  x-cloak
                  x-data="ajaxForm"
                  @submit.prevent="submit">
                @csrf
                <span class="text-sm text-muted block">{{ lexicon('msg_auth_ForgotPasswordPrompt', 'Enter your email and we will send you a reset link.') }}</span>

                <x-metadata name="email"
                            type="email"
                            :label="lexicon('label_common_EmailAddress', 'Email Address')"
                            :required="true"
                            autocomplete="username" />

                <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap gap-md items-center justify-between">
                    <x-button type="button"
                              variant="ghost"
                              size="sm"
                              :tooltip="lexicon('tooltip_auth_BackToLogin', 'Return to login form')"
                              @click="view = 'login'">
                        &larr; {{ lexicon('btn_auth_BackToLogin', 'Back to Login') }}
                    </x-button>
                    <x-button type="submit"
                              variant="primary"
                              :tooltip="lexicon('tooltip_auth_SendResetLink', 'Send password reset link')">
                        {{ lexicon('btn_auth_SendResetLink', 'Send Reset Link') }}
                    </x-button>
                </div>
            </form>
        </div>
        @break

    @case('confirm-password')
        <div class="flex flex-col gap-md w-full">
            <span class="text-sm text-muted block">
                {{ lexicon('msg_auth_ConfirmPasswordPrompt', 'Please confirm your password before continuing.') }}
            </span>

            <form method="POST"
                  action="{{ route('password.confirm') }}"
                  x-data="ajaxForm"
                  @submit.prevent="submit"
                  class="flex flex-col gap-md w-full">
                @csrf
                <x-metadata name="password"
                            type="password"
                            :label="lexicon('label_common_Password', 'Password')"
                            :required="true"
                            autocomplete="current-password" />

                <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                    <x-button type="submit"
                              variant="primary"
                              :tooltip="lexicon('tooltip_auth_ConfirmPassword', 'Confirm your password to proceed')">
                        {{ lexicon('btn_common_Confirm', 'Confirm') }}
                    </x-button>
                </div>
            </form>
        </div>
        @break

    @case('reset-password')
        <form method="POST"
              action="{{ route('password.store') }}"
              x-data="ajaxForm"
              @submit.prevent="submit"
              class="flex flex-col gap-md w-full">
            @csrf
            <x-metadata type="hidden" name="token" :value="$request?->route('token')" />

            <x-metadata name="email"
                        type="email"
                        :label="lexicon('label_common_EmailAddress', 'Email Address')"
                        :value="old('email', $request?->email)"
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

            <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                <x-button type="submit"
                          variant="primary"
                          :tooltip="lexicon('tooltip_auth_SubmitResetPassword', 'Save your new password')">
                    {{ lexicon('btn_auth_ResetPassword', 'Reset Password') }}
                </x-button>
            </div>
        </form>
        @break

    @case('verify-email')
        <div class="flex flex-col gap-md w-full">
            <span class="text-sm text-muted block">
                {{ lexicon('msg_auth_VerificationPrompt', 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?') }}
            </span>

            @if(session('status') === 'verification-link-sent')
                <div x-data
                     x-init="$nextTick(() => { if (window.Swal && typeof window.Swal.toast === 'function') { window.Swal.toast('success', '{{ addslashes(lexicon('msg_auth_VerificationLinkSent', 'A new verification link has been sent to your email address.')) }}'); } })"></div>
            @endif

            <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap gap-md items-center justify-between">
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
        @break

    @case('dynamic')
        @php
            $targetIdentifier = $activeMode ?? ($form->id ?? '');
            $resolvedAction = $action ?: "/form/submit/{$targetIdentifier}";
            $resolvedFields = $fields ?? ($form?->id
                ? \App\Models\Metadata::where('linked_metadata', $form->id)
                    ->where('is_visible', true)
                    ->orderBy('metadata_sort_order')
                    ->get()
                : collect());
        @endphp
        <form method="POST"
              action="{{ $resolvedAction }}"
              x-data="ajaxForm"
              x-ref="dynamicForm"
              @submit.prevent="submit"
              data-loading-title="{{ $loadingTitle ?? lexicon('msg_common_SubmittingForm', 'Submitting form...') }}"
              {{ $attributes->merge(['class' => 'flex flex-col gap-md w-full']) }}>
            @csrf

            @forelse($resolvedFields as $field)
                <x-metadata type="dynamic" :field="$field" />
            @empty
                <span class="font-italic text-muted p-4 text-center block">
                    {{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}
                </span>
            @endforelse

            @if(count($resolvedFields) > 0)
                <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                    @if(isset($bottom))
                        {{ $bottom }}
                    @else
                        <x-button type="submit"
                                  variant="primary"
                                  :tooltip="lexicon('tooltip_common_SubmitForm', 'Submit this form')">
                            {{ lexicon('btn_common_Submit', 'Submit') }}
                        </x-button>
                    @endif
                </div>
            @endif
        </form>
        @break

    @case('modal')
        <form method="{{ $htmlMethod }}"
              @if($xAction) :action="{{ $xAction }}" @elseif($action) action="{{ $action }}" @endif
              @if($ajax)
                  x-data="ajaxForm"
                  @submit.prevent="submit"
              @endif
              @if($loadingTitle) data-loading-title="{{ $loadingTitle }}" @endif
              @if($loadingMsg) data-loading-msg="{{ $loadingMsg }}" @endif
              {{ $attributes->merge(['class' => 'flex flex-col flex-grow overflow-hidden w-full']) }}>
            @if($htmlMethod !== 'GET')
                @csrf
            @endif

            @if($xMethod)
                <template x-if="{{ $xMethod }} && {{$xMethod }} !== 'POST'">
                    <input type="hidden" name="_method" :value="{{ $xMethod }}">
                </template>
            @elseif($needsSpoofing)
                @method($upperMethod)
            @endif

            <div class="p-panel-body overflow-y-auto flex flex-col gap-md flex-grow">
                {{ $slot }}
            </div>

            @if(isset($bottom))
                <div class="{{ $footerClass ?? 'bg-panel-sub border-t-actions p-4 flex flex-row flex-wrap justify-end items-center gap-sm' }}">
                    {{ $bottom }}
                </div>
            @endif
        </form>
        @break

    @case('default')
    @default
        <form method="{{ $htmlMethod }}"
              @if($xAction) :action="{{ $xAction }}" @elseif($action) action="{{ $action }}" @endif
              @if($ajax)
                  x-data="ajaxForm"
                  @submit.prevent="submit"
              @endif
              @if($loadingTitle) data-loading-title="{{ $loadingTitle }}" @endif
              @if($loadingMsg) data-loading-msg="{{ $loadingMsg }}" @endif
              {{ $attributes->merge(['class' => 'flex flex-col gap-md w-full']) }}>
            @if($htmlMethod !== 'GET')
                @csrf
            @endif

            @if($xMethod)
                <template x-if="{{ $xMethod }} && {{$xMethod }} !== 'POST'">
                    <input type="hidden" name="_method" :value="{{ $xMethod }}">
                </template>
            @elseif($needsSpoofing)
                @method($upperMethod)
            @endif

            {{ $slot }}

            @if(isset($bottom))
                <div class="{{ $footerClass ?? 'border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm' }}">
                    {{ $bottom }}
                </div>
            @endif
        </form>
@endswitch