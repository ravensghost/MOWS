@extends('layouts.app')

@section('title', lexicon('public_label_dashboard', 'Dashboard & Profile'))

@section('content')
<div x-data @ajax-success.window="window.updateDOM_US($refs, ['loaStatusPanel', 'profileFormFields'])">
    
    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon('public_label_dashboard', 'Dashboard & Profile')" />

    <x-admin-grid-wrapper :page="$systemPage">

        <!-- BLOCK: Profile Information & Leave of Absence (LOA) -->
        <x-slot:profileinformation>
            <div class="flex flex-col gap-md h-full">

                <!-- 1. Profile Information Panel -->
                <x-accordion-panel :title="lexicon('label_Dashboard_ProfileInformation', 'Profile Information')" :expanded="false">
                    <form method="POST" action="{{ route('profile.update') }}" x-data="ajaxForm" x-ref="profileForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        @method('patch')
                        
                        <div class="flex flex-col gap-md" x-ref="profileFormFields">
                            <x-form-input name="name" 
                                        :label="lexicon('label_common_Name', 'Name')" 
                                        :value="old('name', auth()->user()->name)" 
                                        :required="true" autocomplete="name" />
                            
                            <div class="flex flex-col gap-xs">
                                <x-form-input name="email" type="email" 
                                            :label="lexicon('label_common_EmailAddress', 'Email Address')" 
                                            :value="old('email', auth()->user()->email)" 
                                            :required="true" autocomplete="username" />
                                <div class="flex flex-row items-center justify-end">
                                    <x-button type="button" variant="secondary" size="sm"
                                              x-clipboard="'{{ addslashes(auth()->user()->email) }}'"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Users_CopyEmail', 'Copy email address to clipboard')) }}'">
                                        <i class="bi bi-clipboard"></i>
                                        <span>{{ lexicon('btn_LibraryAudit_CopyEmail', 'Copy Email') }}</span>
                                    </x-button>
                                </div>
                            </div>

                            <div class="pt-2 mt-2 flex flex-col gap-md">
                                <span class="text-xs font-bold text-muted uppercase tracking-wider">{{ lexicon('label_Profile_SocialMedia', 'Social Media Links') }}</span>
                                
                                <x-form-input name="twitter_handle" prefix="@" 
                                            :label="lexicon('label_Profile_Twitter', 'Twitter (X) Username')" 
                                            :value="old('twitter_handle', auth()->user()->twitter_handle)" 
                                            :placeholder="lexicon('label_Profile_TwitterPlaceholder', 'username')"
                                            :tooltip="lexicon('tooltip_Profile_TwitterHandle', 'Enter your X/Twitter handle without the @ symbol')" />

                                <x-form-input name="facebook_handle" 
                                            :label="lexicon('label_Profile_Facebook', 'Facebook Username or URL')" 
                                            :value="old('facebook_handle', auth()->user()->facebook_handle)" 
                                            :placeholder="lexicon('label_Profile_FacebookPlaceholder', 'username or full URL')"
                                            :tooltip="lexicon('tooltip_Profile_FacebookHandle', 'Enter your Facebook profile username or URL')" />
                            </div>
                        </div>

                        <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                            <x-button type="submit" variant="primary" x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_SaveProfile', 'Save your profile details')) }}'">
                                {{ lexicon('btn_common_SaveChanges', 'Save Changes') }}
                            </x-button>
                        </div>
                    </form>
                </x-accordion-panel>

                <!-- 2. Leave of Absence (LOA / ELOA) Panel -->
                <x-accordion-panel :title="lexicon('label_Dashboard_LeaveOfAbsence', 'Leave of Absence (LOA)')" :expanded="(bool) ($user->is_on_loa ?? false)">
                    <div class="flex flex-col gap-md" x-ref="loaStatusPanel" x-data="{ isOnLoa: '{{ ($user->is_on_loa ?? false) ? '1' : '0' }}' }">
                        
                        <div class="pb-4 flex flex-row flex-wrap items-center justify-between gap-md">
                            <span class="text-xs font-bold text-muted uppercase">{{ lexicon('label_common_Status', 'Status') }}</span>
                            @if($user->is_on_loa)
                                @php
                                    $daysRemaining = $user->loa_return_date ? now()->startOfDay()->diffInDays($user->loa_return_date, false) : 0;
                                    $loaBadgeLabel = $daysRemaining > 30
                                        ? lexicon('label_Dashboard_StatusEloa', 'ELOA')
                                        : lexicon('label_Dashboard_StatusLoa', 'LOA');
                                @endphp
                                <x-badge variant="warning" x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_LoaStatus', 'Your characters are currently marked as on leave')) }}'">
                                    {{ $loaBadgeLabel }} @if($user->loa_return_date) ({{ $user->loa_return_date->format('Y-m-d') }}) @endif
                                </x-badge>
                            @else
                                <x-badge variant="success" x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_ActiveStatus', 'Your characters are currently on active duty')) }}'">
                                    {{ lexicon('label_ServiceRecords_StatusActive', 'Active Duty') }}
                                </x-badge>
                            @endif
                        </div>

                        <span class="text-xs text-muted block">
                            {{ lexicon('msg_Dashboard_LoaDescription', 'Going away? Set a return date to place your characters on LOA (up to 30 days) or ELOA (over 30 days) and log it in your service record.') }}
                        </span>

                        <form method="POST" action="{{ route('dashboard.loa') }}" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                            @csrf
                            <div class="flex flex-col gap-xs"
                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_DutyAvailability', 'Select whether you are on active duty or taking leave')) }}'">
                                <label for="is_on_loa" class="text-xs font-bold text-input-muted">{{ lexicon('label_Dashboard_DutyAvailability', 'Duty Availability') }}</label>
                                <select id="is_on_loa" name="is_on_loa" x-model="isOnLoa"
                                        x-init="
                                            if ($el._tomselect) { $el._tomselect.destroy(); }
                                            const ts = new window.TomSelect($el, {
                                                create: false,
                                                controlInput: null,
                                                onChange: (val) => { isOnLoa = String(val); }
                                            });
                                            $watch('isOnLoa', (val) => {
                                                if (ts.getValue() !== String(val)) {
                                                    ts.setValue(String(val), true);
                                                }
                                            });
                                        "
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                    <option value="0">{{ lexicon('label_ServiceRecords_StatusActive', 'Active Duty') }}</option>
                                    <option value="1">{{ lexicon('label_Dashboard_OnLeave', 'On Leave (LOA / ELOA)') }}</option>
                                </select>
                            </div>

                            <div x-cloak x-show="isOnLoa === '1'">
                                <x-form-input id="loa_return_date" name="loa_return_date" type="text"
                                            :label="lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date')"
                                            :value="optional($user->loa_return_date)->format('Y-m-d')"
                                            :placeholder="lexicon('label_Users_ReturnDatePlaceholder', 'YYYY-MM-DD')"
                                            :tooltip="lexicon('tooltip_Dashboard_ReturnDate', 'Select the date you expect to return to active duty')"
                                            x-init="
                                                if ($el._flatpickr) { $el._flatpickr.destroy(); }
                                                window.flatpickr($el, { minDate: 'today', dateFormat: 'Y-m-d', allowInput: true });
                                            "
                                            x-bind:required="isOnLoa === '1'" />
                            </div>

                            <div x-cloak x-show="isOnLoa === '1'" class="flex flex-col gap-xs">
                                <label for="loa_reason" class="text-xs font-bold text-input-muted">
                                    <span>{{ lexicon('label_ServiceRecords_ReasonJustification', 'Reason / Note (Optional)') }}</span>
                                </label>
                                <textarea id="loa_reason" name="reason" rows="2"
                                          placeholder="{{ lexicon('label_Dashboard_LoaReasonPlaceholder', 'e.g. Holiday, exams...') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_LoaReason', 'Optional note logged in your service record')) }}'"
                                          x-init="
                                              window.autosize($el);
                                              $watch('isOnLoa', (val) => {
                                                  if (val === '1') {
                                                      $nextTick(() => window.autosize.update($el));
                                                  }
                                              });
                                          "
                                          class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"></textarea>
                            </div>

                            <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                                <x-button type="submit" variant="primary" x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_UpdateLoa', 'Update your duty availability and character statuses')) }}'">
                                    {{ lexicon('btn_Dashboard_UpdateLoa', 'Update Availability') }}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </x-accordion-panel>

            </div>
            <div class="pt-2 mt-2 flex flex-col gap-md">
                <x-accordion-panel :title="lexicon('label_Profile_Preferences', 'Account Preferences')" :expanded="false">             
                    <div class="flex flex-row items-center justify-between gap-md panel p-3">
                        <div class="flex flex-col">
                            <label for="content_shield_bypass" class="text-xs font-bold text-main">{{ lexicon('label_Profile_ContentShieldBypass', 'Auto-Bypass Mature Content Shield') }}</label>
                            <span class="text-xs text-muted">{{ lexicon('msg_Profile_ContentShieldHint', 'Automatically reveal mature content without clicking warning gates.') }}</span>
                        </div>
                        <input type="hidden" name="content_shield_bypass" value="0">
                        <input type="checkbox" id="content_shield_bypass" name="content_shield_bypass" value="1"
                            {{ old('content_shield_bypass', auth()->user()->content_shield_bypass) ? 'checked' : '' }}
                            class="form-checkbox h-5 w-5 text-accent rounded">
                    </div>
                </x-accordion-panel>
            </div>
        </x-slot:profileinformation>

        <!-- BLOCK: Update Password -->
        <x-slot:updatepassword>
            <x-accordion-panel :title="lexicon('label_Dashboard_UpdatePassword', 'Update Password')" :expanded="false" class="h-full">
                <div class="flex flex-col gap-md">
                    <span class="text-sm text-muted block">{{ lexicon('msg_Dashboard_UpdatePassword', 'Ensure your account is using a long, random password to stay secure.') }}</span>
                    <form method="POST" action="{{ route('password.update') }}" x-data="ajaxForm" x-ref="passwordForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        @method('put')
                        
                        <x-form-input id="current_password" name="current_password" type="password"
                                    :label="lexicon('label_common_CurrentPassword', 'Current Password')"
                                    autocomplete="current-password" :required="true" />

                        <x-form-input id="password" name="password" type="password"
                                    :label="lexicon('label_common_NewPassword', 'New Password')"
                                    autocomplete="new-password" :required="true" />

                        <x-form-input id="password_confirmation" name="password_confirmation" type="password"
                                    :label="lexicon('label_common_ConfirmPassword', 'Confirm Password')"
                                    autocomplete="new-password" :required="true" />

                        <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                            <x-button type="submit" variant="primary" x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_UpdatePassword', 'Save your new account password')) }}'">
                                {{ lexicon('btn_common_UpdatePassword', 'Update Password') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </x-accordion-panel>
        </x-slot:updatepassword>

        <!-- BLOCK: Delete Account -->
        <x-slot:deleteaccount>
            <x-accordion-panel :title="lexicon('label_Dashboard_DeleteAccount', 'Delete Account')" :expanded="false" class="border-danger h-full">
                <div class="flex flex-col gap-md">
                    <span class="text-sm text-muted block">{{ lexicon('msg_Dashboard_DeleteAccount', 'Once your account is deleted, all resources and data will be permanently deleted.') }}</span>
                    <form method="POST" action="{{ route('profile.destroy') }}" x-data="ajaxForm" x-ref="deleteAccountForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        @method('delete')
                        
                        <x-form-input id="delete_password" name="password" type="password"
                                    :label="lexicon('label_common_Password', 'Password')"
                                    :required="true" />

                        <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                            <x-button type="submit" variant="danger" x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_DeleteAccount', 'Permanently delete your user account and data')) }}'">
                                {{ lexicon('btn_Dashboard_PermanentlyDeleteAccount', 'Permanently Delete Account') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </x-accordion-panel>
        </x-slot:deleteaccount>

    </x-admin-grid-wrapper>
</div>
@endsection