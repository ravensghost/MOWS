{{-- resources/views/admin/users.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_users', 'User & Clearance Management'))

@section('content')
<div x-data="loaHubManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="loaHubTableContent"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_users', 'User & Clearance Management')"
                           :subtitle="lexicon('desc_admin_users', 'Manage registered player accounts, Leave of Absence (LOA / ELOA) dates, and Spatie clearance roles.')"
                           icon="bi-people">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="user_search"
                                id="user-search-input"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_Users_SearchPlaceholder', 'Search by username or email...')"
                                :tooltip="lexicon('tooltip_Users_SearchInput', 'Instantaneously filter registered users using fuzzy search')" />

                    <x-badge type="pill"
                             variant="warning"
                             icon="bi-shield-lock"
                             :tooltip="lexicon('tooltip_Users_WebmasterOnly', 'Restricted to authorised administrators')">
                        {{ lexicon('label_Users_Subtitle', 'Admin Clearance Required') }}
                    </x-badge>
                </x-slot:actions>
            </x-page-header>

            <div x-ref="table-wrapper">
                <x-tab type="accordion"
                       :title="lexicon('label_Users_AllUsers', 'All Registered Player Accounts')"
                       icon="bi-person-lines-fill"
                       :open="true">
                    <x-table type="admin"
                             id="users-table-content"
                             x-ref="usersTableContent"
                             :headers="[
                                 lexicon('label_Users_UsernameEmail', 'Username / Email'),
                                 lexicon('label_Users_CurrentRole', 'Current Role'),
                                 lexicon('label_Dashboard_LeaveOfAbsence', 'Leave of Absence (LOA / ELOA)'),
                                 lexicon('label_Users_AssignRole', 'Assign Clearance Role')
                             ]"
                             :emptyText="lexicon('msg_Users_NoUsers', 'No registered users found.')">
                        @foreach($users ?? [] as $user)
                            @php
                                $returnDateObj = $user->loa_return_date ? \Carbon\Carbon::parse($user->loa_return_date) : null;
                                $daysAway = ($user->is_on_loa && $returnDateObj)
                                    ? now()->startOfDay()->diffInDays($returnDateObj, false)
                                    : 0;
                                $loaBadge = $daysAway > 30
                                    ? lexicon('label_Users_StatusEloa', 'ELOA')
                                    : lexicon('label_Users_StatusLoa', 'LOA');
                                $activeRoleName = $user->roles->first()?->name ?? $user->role ?? 'user';
                                $roleVariant = in_array($activeRoleName, ['admin', 'webmaster'], true) ? 'danger' : 'success';
                            @endphp
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-search-item
                                data-loa-user-item
                                data-id="{{ $user->id }}"
                                data-username="{{ $user->name }}"
                                data-name="{{ $user->name }} {{ $user->email }} {{ $activeRoleName }}"
                                x-data="{
                                    role: '{{ addslashes($activeRoleName) }}',
                                    isOnLoa: '{{ $user->is_on_loa ? '1' : '0' }}'
                                }"
                                x-show="isItemVisible({{ $user->id }})">
                                <td class="p-4">
                                    <span class="font-bold text-h block">{{ $user->name }}</span>
                                    @if(!empty($user->email))
                                        <div class="flex flex-row items-center gap-xs mt-1">
                                            <span class="text-xs text-muted">{{ $user->email }}</span>
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-clipboard"
                                                      :tooltip="lexicon('tooltip_Users_CopyEmail', 'Copy email address to clipboard')"
                                                      x-clipboard="'{{ addslashes($user->email) }}'" />
                                        </div>
                                    @endif
                                </td>

                                <td class="p-4">
                                    <x-badge type="pill"
                                             :variant="$roleVariant"
                                             :tooltip="lexicon('tooltip_Users_CurrentRoleBadge', 'Assigned system role')"
                                             x-text="role">
                                        {{ $activeRoleName }}
                                    </x-badge>
                                </td>

                                <td class="p-4">
                                    @can('manage_users')
                                        <x-meta type="default"
                                                action="/admin/users/{{ $user->id }}/loa"
                                                method="PUT"
                                                :loadingTitle="lexicon('msg_Users_UpdatingLoa', 'Updating Leave Status...')">
                                            <div class="flex flex-row flex-wrap items-center gap-xs">
                                                <div class="w-36">
                                                    <x-metadata type="select"
                                                                name="is_on_loa"
                                                                xModel="isOnLoa"
                                                                :tooltip="lexicon('tooltip_Users_SelectLoaStatus', 'Set duty or leave status')">
                                                        <option value="0">{{ lexicon('label_ServiceRecords_StatusActive', 'Active') }}</option>
                                                        <option value="1">{{ $user->is_on_loa ? $loaBadge : lexicon('label_Users_OptLoaEloa', 'LOA / ELOA') }}</option>
                                                    </x-metadata>
                                                </div>

                                                <div x-show="isOnLoa === '1'" x-cloak class="w-40">
                                                    <x-metadata type="chronological"
                                                                name="loa_return_date"
                                                                :value="$returnDateObj ? $returnDateObj->format('Y-m-d') : ''"
                                                                :placeholder="lexicon('label_Users_ReturnDatePlaceholder', 'YYYY-MM-DD')"
                                                                :tooltip="lexicon('tooltip_Users_LoaReturnDate', 'Expected return date from leave')"
                                                                ::required="isOnLoa === '1'" />
                                                </div>

                                                <x-button type="submit"
                                                          variant="secondary"
                                                          size="sm"
                                                          icon="bi-check2-circle"
                                                          :tooltip="lexicon('tooltip_Users_SaveLoaStatus', 'Save leave of absence status')">
                                                    {{ lexicon('btn_common_Save', 'Save') }}
                                                </x-button>
                                            </div>
                                        </x-meta>
                                    @endcan
                                </td>

                                <td class="p-4 text-right">
                                    @can('manage_users')
                                        <x-meta type="default"
                                                action="/admin/users/{{ $user->id }}/role"
                                                method="PUT"
                                                :loadingTitle="lexicon('msg_Users_UpdatingRole', 'Updating Role...')">
                                            <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                                <div class="w-48 text-left"
                                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Users_SelectRole', 'Select system permission role')) }}'">
                                                    <select name="role"
                                                            x-model="role"
                                                            aria-label="{{ lexicon('label_Users_AssignRole', 'Assign Role') }}"
                                                            x-init="window.Select && window.Select.init($el, {
                                                                create: false,
                                                                controlInput: null,
                                                                onChange: (val) => { role = val; }
                                                            })"
                                                            class="w-full h-input p-input bg-input text-input border-input border-rounded text-xs">
                                                        @if(isset($roles) && $roles->count() > 0)
                                                            @foreach($roles as $roleOption)
                                                                <option value="{{ $roleOption->name }}" @selected($activeRoleName === $roleOption->name)>{{ ucwords($roleOption->name) }}</option>
                                                            @endforeach
                                                        @else
                                                            <option value="user" @selected($activeRoleName === 'user')>{{ lexicon('label_Users_RoleUser', 'User') }}</option>
                                                            <option value="senior user" @selected($activeRoleName === 'senior user')>{{ lexicon('label_Users_RoleSeniorUser', 'Senior User') }}</option>
                                                            <option value="admin" @selected($activeRoleName === 'admin')>{{ lexicon('label_Users_RoleAdmin', 'Admin') }}</option>
                                                            <option value="webmaster" @selected($activeRoleName === 'webmaster')>{{ lexicon('label_Users_RoleWebmaster', 'Webmaster') }}</option>
                                                        @endif
                                                    </select>
                                                </div>

                                                <x-button type="submit"
                                                          variant="primary"
                                                          size="sm"
                                                          icon="bi-person-gear"
                                                          :disabled="$user->id === auth()->id()"
                                                          :tooltip="lexicon('tooltip_Users_UpdateRole', 'Update user role')">
                                                    {{ lexicon('btn_common_Update', 'Update') }}
                                                </x-button>
                                            </div>
                                        </x-meta>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-tab>
            </div>
        </x-slot:content>
    </x-grid-wrapper>
</div>
@endsection