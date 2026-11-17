{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('public_label_dashboard', 'Dashboard & Profile'))

@section('content')
    @php
        $currentUser = $user ?? auth()->user();
        $isUserOnLoa = (bool) ($currentUser?->is_on_loa ?? false);
        $formattedReturnDate = optional($currentUser?->loa_return_date)->format('Y-m-d') ?? '';
        $mappedInUniverseReturnDate = ($currentUser?->loa_return_date && function_exists('world_date'))
            ? world_date($currentUser->loa_return_date)
            : null;
    @endphp

    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon('public_label_dashboard', 'Dashboard & Profile')" icon="bi-person-Lines-fill" />

    <x-grid-wrapper :book="$systemBook">

        <!-- BLOCK: Profile Information & Leave of Absence (LOA) -->
        <x-slot:profileinformation>
            <div class="flex flex-col gap-md h-full">

                <!-- 1. Profile Information Panel -->
                <x-tab type="accordion"
                       :title="lexicon('label_Dashboard_ProfileInformation', 'Profile Information')"
                       icon="bi-person-vcard"
                       :open="false">
                    <form id="profile-update-form"
                          method="POST"
                          action="{{ route('profile.update') }}"
                          x-data="ajaxForm"
                          x-ref="profileForm"
                          @submit.prevent="submit"
                          class="flex flex-col gap-md">
                        @csrf
                        @method('patch')

                        <div class="flex flex-col gap-md" x-ref="profileFormFields">
                            <x-metadata type="text"
                                        name="name"
                                        :label="lexicon('label_common_Name', 'Name')"
                                        :value="old('name', $currentUser?->name)"
                                        :required="true"
                                        autocomplete="name" />

                            <div class="flex flex-col gap-xs">
                                <x-metadata type="email"
                                            name="email"
                                            :label="lexicon('label_common_EmailAddress', 'Email Address')"
                                            :value="old('email', $currentUser?->email)"
                                            :required="true"
                                            autocomplete="username" />
                                <div class="flex flex-row items-center justify-end">
                                    <x-button type="button"
                                              variant="secondary"
                                              size="sm"
                                              icon="bi-clipboard"
                                              :tooltip="lexicon('tooltip_Users_CopyEmail', 'Copy email address to clipboard')"
                                              x-clipboard="'{{ addslashes($currentUser?->email ?? '') }}'">
                                        {{ lexicon('btn_LibraryAudit_CopyEmail', 'Copy Email') }}
                                    </x-button>
                                </div>
                            </div>

                            <div class="pt-2 mt-2 border-t-panel flex flex-col gap-md">
                                <span class="text-xs font-bold text-muted uppercase text-wider">{{ lexicon('label_Profile_SocialMedia', 'Social Media Links') }}</span>

                                <x-metadata type="text"
                                            name="twitter_handle"
                                            prefix="@"
                                            :label="lexicon('label_Profile_Twitter', 'Twitter (X) Username')"
                                            :value="old('twitter_handle', $currentUser?->twitter_handle)"
                                            :placeholder="lexicon('label_Profile_TwitterPlaceholder', 'username')"
                                            :tooltip="lexicon('tooltip_Profile_TwitterHandle', 'Enter your X/Twitter handle without the @ symbol')" />

                                <x-metadata type="text"
                                            name="facebook_handle"
                                            :label="lexicon('label_Profile_Facebook', 'Facebook Username or URL')"
                                            :value="old('facebook_handle', $currentUser?->facebook_handle)"
                                            :placeholder="lexicon('label_Profile_FacebookPlaceholder', 'username or full URL')"
                                            :tooltip="lexicon('tooltip_Profile_FacebookHandle', 'Enter your Facebook profile username or URL')" />
                            </div>
                        </div>

                        <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                            <x-button type="submit"
                                      variant="primary"
                                      :tooltip="lexicon('tooltip_Dashboard_SaveProfile', 'Save your profile details')">
                                {{ lexicon('btn_common_SaveChanges', 'Save Changes') }}
                            </x-button>
                        </div>
                    </form>
                </x-tab>

                <!-- 2. Leave of Absence (LOA / ELOA) Panel -->
                <x-tab type="accordion"
                       :title="lexicon('label_Dashboard_LeaveOfAbsence', 'Leave of Absence (LOA)')"
                       icon="bi-calendar-range"
                       :open="$isUserOnLoa">
                    <div class="flex flex-col gap-md"
                         x-ref="loaStatusPanel"
                         x-data="{
                             isOnLoa: '{{ $isUserOnLoa ? '1' : '0' }}',
                             initLoaSelect(el) {
                                 if (!window.Select) return;
                                 const ts = window.Select.init(el, {
                                     create: false,
                                     controlInput: null,
                                     onChange: (val) => { this.isOnLoa = String(val); }
                                 });
                                 this.$watch('isOnLoa', (val) => {
                                     if (ts && ts.getValue() !== String(val)) {
                                         ts.setValue(String(val), true);
                                     }
                                 });
                             }
                         }">

                        <div class="pb-3 border-b-panel flex flex-row flex-wrap items-center justify-between gap-md">
                            <span class="text-xs font-bold text-muted uppercase text-wider">{{ lexicon('label_common_Status', 'Status') }}</span>
                            @if($isUserOnLoa)
                                @php
                                    $daysRemaining = $currentUser?->loa_return_date
                                        ? now()->startOfDay()->diffInDays($currentUser->loa_return_date, false)
                                        : 0;
                                    $loaBadgeLabel = $daysRemaining > 30
                                        ? lexicon('label_Dashboard_StatusEloa', 'ELOA')
                                        : lexicon('label_Dashboard_StatusLoa', 'LOA');
                                @endphp
                                <x-badge variant="warning"
                                         :tooltip="lexicon('tooltip_Dashboard_LoaStatus', 'Your characters are currently marked as on leave')">
                                    {{ $loaBadgeLabel }} @if($formattedReturnDate) ({{ $formattedReturnDate }}) @endif
                                </x-badge>
                            @else
                                <x-badge variant="success"
                                         :tooltip="lexicon('tooltip_Dashboard_ActiveStatus', 'Your characters are currently on active duty')">
                                    {{ lexicon('label_ServiceRecords_StatusActive', 'Active Duty') }}
                                </x-badge>
                            @endif
                        </div>

                        <span class="text-xs text-muted block">
                            {{ lexicon('msg_Dashboard_LoaDescription', 'Going away? Set a return date to place your characters on LOA (up to 30 days) or ELOA (over 30 days) and log it in your service record.') }}
                        </span>

                        <form method="POST"
                              action="{{ route('dashboard.loa') }}"
                              x-data="ajaxForm"
                              @submit.prevent="submit"
                              class="flex flex-col gap-md">
                            @csrf

                            <x-metadata type="select"
                                        id="is_on_loa"
                                        name="is_on_loa"
                                        :label="lexicon('label_Dashboard_DutyAvailability', 'Duty Availability')"
                                        :tooltip="lexicon('tooltip_Dashboard_DutyAvailability', 'Select whether you are on active duty or taking leave')"
                                        xModel="isOnLoa"
                                        x-init="initLoaSelect($el)">
                                <option value="0">{{ lexicon('label_ServiceRecords_StatusActive', 'Active Duty') }}</option>
                                <option value="1">{{ lexicon('label_Dashboard_OnLeave', 'On Leave (LOA / ELOA)') }}</option>
                            </x-metadata>

                            <div x-cloak x-show="isOnLoa === '1'">
                                <x-metadata type="chronological"
                                            id="loa_return_date"
                                            name="loa_return_date"
                                            :label="lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date')"
                                            :value="$formattedReturnDate"
                                            :placeholder="lexicon('label_Users_ReturnDatePlaceholder', 'YYYY-MM-DD')"
                                            :tooltip="lexicon('tooltip_Dashboard_ReturnDate', 'Select the date you expect to return to active duty')"
                                            :inUniverseDate="$mappedInUniverseReturnDate"
                                            :mappedRealDate="$formattedReturnDate"
                                            x-init="window.Flatpickr && window.Flatpickr.init($el, { enableTime: false, minDate: 'today', dateFormat: 'Y-m-d', allowInput: true })"
                                            x-bind:required="isOnLoa === '1'" />
                            </div>

                            <div x-cloak x-show="isOnLoa === '1'">
                                <x-metadata type="textarea"
                                            id="loa_reason"
                                            name="reason"
                                            rows="2"
                                            :label="lexicon('label_ServiceRecords_ReasonJustification', 'Reason / Note (Optional)')"
                                            :placeholder="lexicon('label_Dashboard_LoaReasonPlaceholder', 'e.g. Holiday, exams...')"
                                            :tooltip="lexicon('tooltip_Dashboard_LoaReason', 'Optional note logged in your service record')"
                                            x-init="
                                                window.Autosize && window.Autosize.init($el);
                                                $watch('isOnLoa', (val) => {
                                                    if (val === '1' && window.Autosize) {
                                                        $nextTick(() => window.Autosize.update($el));
                                                    }
                                                });
                                            " />
                            </div>

                            <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                                <x-button type="submit"
                                          variant="primary"
                                          :tooltip="lexicon('tooltip_Dashboard_UpdateLoa', 'Update your duty availability and character statuses')">
                                    {{ lexicon('btn_Dashboard_UpdateLoa', 'Update Availability') }}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </x-tab>

                <!-- 3. Account Preferences Panel -->
                <x-tab type="accordion"
                       :title="lexicon('label_Profile_Preferences', 'Account Preferences')"
                       icon="bi-sliders"
                       :open="false">
                    <form method="POST"
                          action="{{ route('profile.update') }}"
                          x-data="ajaxForm"
                          @submit.prevent="submit"
                          class="flex flex-col gap-md">
                        @csrf
                        @method('patch')
                        <x-metadata type="hidden" name="name" :value="$currentUser?->name" />
                        <x-metadata type="hidden" name="email" :value="$currentUser?->email" />

                        <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row items-center justify-between gap-md">
                            <div class="flex flex-col gap-xs">
                                <span class="text-xs font-bold text-h">{{ lexicon('label_Profile_ContentShieldBypass', 'Auto-Bypass Mature Content Shield') }}</span>
                                <span class="text-xs text-muted">{{ lexicon('msg_Profile_ContentShieldHint', 'Automatically reveal mature content without clicking warning gates.') }}</span>
                            </div>
                            <input type="hidden" name="content_shield_bypass" value="0">
                            <x-metadata type="checkbox"
                                        id="content_shield_bypass"
                                        name="content_shield_bypass"
                                        value="1"
                                        :tooltip="lexicon('tooltip_Profile_ContentShieldBypass', 'Toggle automatic bypass of mature content warning shields')"
                                        :checked="(bool) old('content_shield_bypass', $currentUser?->content_shield_bypass)" />
                        </div>

                        <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                            <x-button type="submit"
                                      variant="primary"
                                      :tooltip="lexicon('tooltip_Dashboard_SavePreferences', 'Save your account preferences')">
                                {{ lexicon('btn_common_SaveChanges', 'Save Changes') }}
                            </x-button>
                        </div>
                    </form>
                </x-tab>

            </div>
        </x-slot:profileinformation>

        <!-- BLOCK: Update Password -->
        <x-slot:updatepassword>
            <x-tab type="accordion"
                   :title="lexicon('label_Dashboard_UpdatePassword', 'Update Password')"
                   icon="bi-key"
                   :open="false"
                   class="h-full">
                <div class="flex flex-col gap-md">
                    <span class="text-sm text-muted block">{{ lexicon('msg_Dashboard_UpdatePassword', 'Ensure your account is using a long, random password to stay secure.') }}</span>
                    <form method="POST"
                          action="{{ route('password.update') }}"
                          x-data="ajaxForm"
                          x-ref="passwordForm"
                          @submit.prevent="submit"
                          class="flex flex-col gap-md">
                        @csrf
                        @method('put')

                        <x-metadata type="password"
                                    id="current_password"
                                    name="current_password"
                                    :label="lexicon('label_common_CurrentPassword', 'Current Password')"
                                    autocomplete="current-password"
                                    :required="true" />

                        <x-metadata type="password"
                                    id="password"
                                    name="password"
                                    :label="lexicon('label_common_NewPassword', 'New Password')"
                                    autocomplete="new-password"
                                    :required="true" />

                        <x-metadata type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :label="lexicon('label_common_ConfirmPassword', 'Confirm Password')"
                                    autocomplete="new-password"
                                    :required="true" />

                        <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                            <x-button type="submit"
                                      variant="primary"
                                      :tooltip="lexicon('tooltip_Dashboard_UpdatePassword', 'Save your new account password')">
                                {{ lexicon('btn_common_UpdatePassword', 'Update Password') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </x-tab>
        </x-slot:updatepassword>

        <!-- BLOCK: Delete Account -->
        <x-slot:deleteaccount>
            <x-tab type="accordion"
                   :title="lexicon('label_Dashboard_DeleteAccount', 'Delete Account')"
                   icon="bi-exclamation-triangle"
                   :open="false"
                   class="border-danger h-full">
                <div class="flex flex-col gap-md">
                    <span class="text-sm text-muted block">{{ lexicon('msg_Dashboard_DeleteAccount', 'Once your account is deleted, all resources and data will be permanently deleted.') }}</span>
                    <form method="POST"
                          action="{{ route('profile.destroy') }}"
                          x-data="ajaxForm"
                          x-ref="deleteAccountForm"
                          @submit.prevent="submit"
                          class="flex flex-col gap-md">
                        @csrf
                        @method('delete')

                        <x-metadata type="password"
                                    id="delete_password"
                                    name="password"
                                    :label="lexicon('label_common_Password', 'Password')"
                                    :required="true" />

                        <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                            <x-button type="submit"
                                      variant="danger"
                                      :tooltip="lexicon('tooltip_Dashboard_DeleteAccount', 'Permanently delete your user account and data')">
                                {{ lexicon('btn_Dashboard_PermanentlyDeleteAccount', 'Permanently Delete Account') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </x-tab>
        </x-slot:deleteaccount>

    </x-grid-wrapper>
@endsection