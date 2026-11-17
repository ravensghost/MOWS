{{-- resources/views/layouts/partials/auth-modal.blade.php --}}
<div x-data="{ open: false, view: 'login' }"
     @open-auth.window="open = true; view = $event.detail?.view || 'login'"
     @close-auth.window="open = false"
     data-global-sync="auth-modal">

    <x-modal show="open" onClose="open = false">
        <x-slot:top>
            <div class="flex flex-row items-center gap-sm">
                <i class="bi bi-shield-lock text-accent"></i>
                <span x-show="view === 'login'">{{ lexicon('public_label_Login', 'Account Login') }}</span>
                <span x-show="view === 'register'" x-cloak>{{ lexicon('public_label_Register', 'Create Account') }}</span>
                <span x-show="view === 'forgot'" x-cloak>{{ lexicon('public_label_ForgotPassword', 'Reset Password') }}</span>
            </div>
        </x-slot:top>

        <div class="flex flex-col gap-md">
            <!-- LOGIN FORM -->
            <form method="POST"
                  action="/login"
                  class="flex flex-col gap-md"
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

                <div class="border-t-actions pt-4 flex flex-row flex-wrap gap-md items-center justify-between">
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

            <!-- REGISTER FORM -->
            <form method="POST"
                  action="/register"
                  class="flex flex-col gap-md"
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

                <div class="border-t-actions pt-4 flex flex-row flex-wrap gap-md items-center justify-between">
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

            <!-- FORGOT PASSWORD FORM -->
            <form method="POST"
                  action="{{ route('password.email') }}"
                  class="flex flex-col gap-md"
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

                <div class="border-t-actions pt-4 flex flex-row flex-wrap gap-md items-center justify-between">
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
    </x-modal>
</div>