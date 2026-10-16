@extends('layouts.app')

@section('title', lexicon('admin_label_users', 'User Management'))

@section('content')
<div x-data="{
        searchQuery: '',
        matchesSearch(name, email, role) {
            if (!this.searchQuery.trim()) return true;
            const list = [{ name, email, role }];
            const fuse = new window.Fuse(list, {
                keys: ['name', 'email', 'role'],
                threshold: 0.35
            });
            return fuse.search(this.searchQuery.trim()).length > 0;
        }
     }"
     @ajax-success.window="window.updateDOM_US($refs, ['usersTableContent'])">
    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>

            <x-page-header :title="lexicon('admin_label_users', 'User Management')">
                <x-slot:actions>
                    <x-badge variant="warning"
                             class="border"
                             aria-label="{{ lexicon('tooltip_Users_WebmasterOnly', 'Restricted to authorised administrators') }}"
                             x-tooltip="'{{ addslashes(lexicon('tooltip_Users_WebmasterOnly', 'Restricted to authorised administrators')) }}'">
                        <i class="bi bi-shield-lock mr-1" aria-hidden="true"></i>
                        <span>{{ lexicon('label_Users_Subtitle', 'Webmaster Access Only') }}</span>
                    </x-badge>
                </x-slot:actions>
            </x-page-header>

            <x-accordion-panel :title="lexicon('label_Users_AllUsers', 'All Registered Users')" :expanded="true">
                <div class="mb-4 w-full md-w-1-2">
                    <x-form-input name="user_search"
                                  id="user-search-input"
                                  :label="lexicon('label_Users_SearchUsers', 'Filter Users')"
                                  :placeholder="lexicon('label_Users_SearchPlaceholder', 'Search by name, email, or role...')"
                                  :tooltip="lexicon('tooltip_Users_SearchInput', 'Instantaneously filter registered users using fuzzy search')"
                                  xModel="searchQuery" />
                </div>

                <x-table id="users-table-content" x-ref="usersTableContent">
                    <x-slot:headers>
                        <th class="p-4 text-left">{{ lexicon('label_Users_UsernameEmail', 'Username / Email') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_Users_CurrentRole', 'Current Role') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_Dashboard_LeaveOfAbsence', 'Leave of Absence (LOA)') }}</th>
                        <th class="p-4 text-right">{{ lexicon('label_Users_AssignRole', 'Assign Role') }}</th>
                    </x-slot:headers>

                    @forelse($users as $user)
                        @php
                            $daysAway = ($user->is_on_loa && $user->loa_return_date)
                                ? now()->startOfDay()->diffInDays($user->loa_return_date, false)
                                : 0;
                            $loaBadge = $daysAway > 30
                                ? lexicon('label_Users_StatusEloa', 'ELOA')
                                : lexicon('label_Users_StatusLoa', 'LOA');
                            $activeRoleName = $user->roles->first()?->name ?? $user->role;
                        @endphp
                        <tr class="border-b-table-row hover-bg-table-row"
                            x-data="{
                                role: '{{ addslashes($activeRoleName) }}',
                                isOnLoa: '{{ $user->is_on_loa ? '1' : '0' }}'
                            }"
                            x-show="matchesSearch('{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', role)">
                            <td class="p-4 text-left">
                                <span class="font-bold text-p block">{{ $user->name }}</span>
                                @if(!empty($user->email))
                                    <div class="flex flex-row items-center gap-xs mt-1">
                                        <span class="text-xs text-muted">{{ $user->email }}</span>
                                        <button type="button"
                                                class="bg-transparent text-muted border-none hover-text-accent cursor-pointer p-0 text-xs"
                                                x-clipboard="'{{ addslashes($user->email) }}'"
                                                aria-label="{{ lexicon('tooltip_Users_CopyEmail', 'Copy email address to clipboard') }}"
                                                x-tooltip="'{{ addslashes(lexicon('tooltip_Users_CopyEmail', 'Copy email address to clipboard')) }}'">
                                            <i class="bi bi-clipboard" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 text-left">
                                <x-badge class="border"
                                         ::class="role === 'admin' || role === 'webmaster' ? 'bg-badge-danger text-danger-light border-danger' : 'bg-badge-success text-success border-success'"
                                         x-text="role"
                                         aria-label="{{ lexicon('tooltip_Users_CurrentRoleBadge', 'Assigned system role') }}"
                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Users_CurrentRoleBadge', 'Assigned system role')) }}'">
                                </x-badge>
                            </td>
                            <td class="p-4 text-left">
                                @can('manage_users')
                                    <form action="/admin/users/{{ $user->id }}/loa"
                                          method="POST"
                                          class="flex flex-row flex-wrap items-center gap-xs"
                                          x-data="ajaxForm"
                                          @submit.prevent="submit"
                                          data-loading-title="{{ lexicon('msg_Users_UpdatingLoa', 'Updating Leave Status...') }}">
                                        @csrf
                                        @method('PUT')
                                        <select name="is_on_loa"
                                                x-model="isOnLoa"
                                                aria-label="{{ lexicon('label_Dashboard_LeaveOfAbsence', 'Leave of Absence (LOA)') }}"
                                                x-tooltip="'{{ addslashes(lexicon('tooltip_Users_SelectLoaStatus', 'Set duty or leave status')) }}'"
                                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-32 box-border focus-border-accent focus-shadow-accent text-xs">
                                            <option value="0">{{ lexicon('label_ServiceRecords_StatusActive', 'Active') }}</option>
                                            <option value="1">{{ $user->is_on_loa ? $loaBadge : lexicon('label_Users_OptLoaEloa', 'LOA / ELOA') }}</option>
                                        </select>
                                        <input type="text"
                                               name="loa_return_date"
                                               aria-label="{{ lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date') }}"
                                               placeholder="{{ lexicon('label_Users_ReturnDatePlaceholder', 'YYYY-MM-DD') }}"
                                               x-cloak
                                               x-show="isOnLoa === '1'"
                                               x-init="
                                                   let fp = window.flatpickr($el, { dateFormat: 'Y-m-d', allowInput: true });
                                                   $cleanup(() => fp.destroy());
                                               "
                                               value="{{ optional($user->loa_return_date)->format('Y-m-d') }}"
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_Users_LoaReturnDate', 'Expected return date from leave')) }}'"
                                               class="bg-input text-input border-input rounded-input p-input outline-none h-input w-40 box-border focus-border-accent focus-shadow-accent text-xs"
                                               :required="isOnLoa === '1'">
                                        <x-button type="submit"
                                                  variant="secondary"
                                                  class="p-btn-sm text-xs"
                                                  aria-label="{{ lexicon('tooltip_Users_SaveLoaStatus', 'Save leave of absence status') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Users_SaveLoaStatus', 'Save leave of absence status')) }}'">
                                            <i class="bi bi-check2-circle mr-1" aria-hidden="true"></i>
                                            <span>{{ lexicon('btn_common_Save', 'Save Changes') }}</span>
                                        </x-button>
                                    </form>
                                @endcan
                            </td>
                            <td class="p-4 text-right">
                                @can('manage_users')
                                    <form action="/admin/users/{{ $user->id }}/role"
                                          method="POST"
                                          class="flex flex-row flex-wrap items-center justify-end gap-sm"
                                          x-data="ajaxForm"
                                          @submit.prevent="submit"
                                          data-loading-title="{{ lexicon('msg_Users_UpdatingRole', 'Updating Role...') }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="w-48 text-left"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_Users_SelectRole', 'Select system permission role')) }}'">
                                            <select name="role"
                                                    x-model="role"
                                                    aria-label="{{ lexicon('label_Users_AssignRole', 'Assign Role') }}"
                                                    x-init="
                                                        let ts = new window.TomSelect($el, {
                                                            create: false,
                                                            controlInput: null,
                                                            onChange: (val) => { role = val; }
                                                        });
                                                        $watch('role', (val) => {
                                                            if (ts.getValue() !== val) ts.setValue(val, true);
                                                        });
                                                        $cleanup(() => ts.destroy());
                                                    "
                                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent text-xs">
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
                                                  class="p-btn-sm text-sm"
                                                  :disabled="$user->id === auth()->id()"
                                                  aria-label="{{ lexicon('tooltip_Users_UpdateRole', 'Update user role') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Users_UpdateRole', 'Update user role')) }}'">
                                            <i class="bi bi-person-gear mr-1" aria-hidden="true"></i>
                                            <span>{{ lexicon('btn_common_Update', 'Update') }}</span>
                                        </x-button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-muted italic">
                                <span>{{ lexicon('msg_Users_NoUsers', 'No users found.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-accordion-panel>

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection