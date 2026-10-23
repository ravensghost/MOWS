<div data-global-sync="auth-modal" x-data="{ open: false, view: 'login' }" @open-auth.window="open = true">
    <x-modal show="open" maxWidth="max-w-lg">
        <x-slot:top>
            <span x-text="
                view === 'login' ? '{{ addslashes(lexicon('label_auth_SystemAuthentication', 'System Authentication')) }}' : 
                (view === 'register' ? '{{ addslashes(lexicon('label_auth_CreateAccountTitle', 'Create Account')) }}' : 
                '{{ addslashes(lexicon('label_auth_ResetPassword', 'Reset Password')) }}')
            "></span>
        </x-slot:top>

        <div class="flex flex-col gap-md">
            <!-- LOGIN FORM -->
            <form method="POST" action="/login" class="flex flex-col gap-md" x-show="view === 'login'" x-data="ajaxForm" @submit.prevent="submit">
                @csrf
                <x-form-input name="email"
                              type="email"
                              :label="lexicon('label_common_EmailAddress', 'Email Address')"
                              :required="true"
                              autocomplete="username" />
                
                <x-form-input name="password"
                              type="password"
                              :label="lexicon('label_common_Password', 'Password')"
                              :required="true"
                              autocomplete="current-password" />
                
                <label class="flex flex-row items-center gap-xs cursor-pointer select-none">
                    <input type="checkbox" name="remember" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid checkbox-engine cursor-pointer">
                    <span class="text-sm font-bold text-p">{{ lexicon('label_auth_RememberMe', 'Remember me') }}</span>
                </label>

                <div class="border-t-actions pt-6 flex flex-row flex-wrap gap-md items-center justify-between">
                    <x-button type="button"
                              variant="text"
                              class="text-sm"
                              @click="view = 'forgot'"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_ForgotPassword', 'Reset a forgotten password')) }}'">
                        {{ lexicon('btn_auth_ForgotPassword', 'Forgot password?') }}
                    </x-button>
                    <div class="flex flex-row flex-wrap gap-sm items-center">
                        <x-button type="button"
                                  variant="secondary"
                                  @click="view = 'register'"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_auth_SwitchToRegister', 'Switch to account registration')) }}'">
                            {{ lexicon('btn_common_Register', 'Register') }}
                        </x-button>
                        <x-button type="submit"
                                  variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_auth_SubmitLogin', 'Sign in to your account')) }}'">
                            {{ lexicon('btn_common_LogIn', 'Log In') }}
                        </x-button>
                    </div>
                </div>
            </form>

            <!-- REGISTER FORM -->
            <form method="POST" action="/register" class="flex flex-col gap-md" x-show="view === 'register'" x-cloak x-data="ajaxForm" @submit.prevent="submit">
                @csrf
                <x-form-input name="name"
                              type="text"
                              :label="lexicon('label_common_FullName', 'Full Name')"
                              :required="true"
                              autocomplete="name" />
                
                <x-form-input name="email"
                              type="email"
                              :label="lexicon('label_common_EmailAddress', 'Email Address')"
                              :required="true"
                              autocomplete="username" />
                
                <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                    <x-form-input name="password"
                                  type="password"
                                  :label="lexicon('label_common_Password', 'Password')"
                                  :required="true"
                                  autocomplete="new-password" />

                    <x-form-input name="password_confirmation"
                                  type="password"
                                  :label="lexicon('label_common_Confirm', 'Confirm')"
                                  :required="true"
                                  autocomplete="new-password" />
                </div>
                
                <div class="border-t-actions pt-6 flex flex-row flex-wrap gap-md items-center justify-between">
                    <x-button type="button"
                              variant="text"
                              class="text-sm"
                              @click="view = 'login'"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_BackToLogin', 'Return to login form')) }}'">
                        &larr; {{ lexicon('btn_auth_BackToLogin', 'Back to Login') }}
                    </x-button>
                    <x-button type="submit"
                              variant="primary"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_SubmitRegister', 'Create your new account')) }}'">
                        {{ lexicon('btn_auth_CreateAccount', 'Create Account') }}
                    </x-button>
                </div>
            </form>

            <!-- FORGOT PASSWORD FORM -->
            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-md" x-show="view === 'forgot'" x-cloak x-data="ajaxForm" @submit.prevent="submit">
                @csrf
                <span class="text-sm text-muted block">{{ lexicon('msg_auth_ForgotPasswordPrompt', 'Enter your email and we will send you a reset link.') }}</span>
                
                <x-form-input name="email"
                              type="email"
                              :label="lexicon('label_common_EmailAddress', 'Email Address')"
                              :required="true"
                              autocomplete="username" />
                
                <div class="border-t-actions pt-6 flex flex-row flex-wrap gap-md items-center justify-between">
                    <x-button type="button"
                              variant="text"
                              class="text-sm"
                              @click="view = 'login'"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_BackToLogin', 'Return to login form')) }}'">
                        &larr; {{ lexicon('btn_auth_BackToLogin', 'Back to Login') }}
                    </x-button>
                    <x-button type="submit"
                              variant="primary"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_SendResetLink', 'Send password reset link')) }}'">
                        {{ lexicon('btn_auth_SendResetLink', 'Send Reset Link') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>