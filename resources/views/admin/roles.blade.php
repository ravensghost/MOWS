{{-- resources/views/admin/roles.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_roles', 'Access Clearance & Roles Hub'))

@section('content')
<div x-data="accessClearanceRoleManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="tableWrapper"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_roles', 'Access Clearance & Roles Hub')"
                           :subtitle="lexicon('desc_admin_roles', 'Manage Spatie access clearance roles, granular system permissions, and navigation clearance audits.')"
                           icon="bi-shield-lock" />

            @can('manage_roles')
                <div class="flex flex-col gap-lg">
                    {{-- Create Role Panel --}}
                    <x-tab type="accordion"
                           :title="lexicon('admin_label_CreateEditRole', 'Create Access Clearance Role')"
                           icon="bi-shield-plus"
                           :open="false">
                        <x-meta type="default"
                                :action="route('admin.access_clearance_roles.store')"
                                method="POST"
                                :loadingTitle="lexicon('msg_Roles_SavingRole', 'Saving Role...')">

                            <x-metadata type="text"
                                        name="name"
                                        id="create-role-name"
                                        :label="lexicon('label_Roles_RoleName', 'Role Name')"
                                        :placeholder="lexicon('label_Roles_RoleNamePlaceholder', 'e.g. senior gamemaster')"
                                        :tooltip="lexicon('tooltip_Roles_RoleNameInput', 'Enter unique role name')"
                                        :required="true" />

                            <div class="flex flex-col gap-xs"
                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Roles_AssignPermissionsSelect', 'Search and select one or more system permissions for this role')) }}'">
                                <label for="create-role-permissions" class="text-xs font-bold text-input-muted uppercase text-wider">
                                    {{ lexicon('label_Roles_AssignPermissions', 'Assign System Permissions') }}
                                </label>
                                <select id="create-role-permissions"
                                        name="permissions[]"
                                        multiple
                                        aria-label="{{ lexicon('label_Roles_AssignPermissions', 'Assign Permissions') }}"
                                        class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm"
                                        placeholder="{{ lexicon('label_Roles_SelectPermissionsPlaceholder', 'Select permissions...') }}">
                                    @foreach($allPermissions ?? [] as $permission)
                                        <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="border-t-actions pt-4 flex flex-row justify-end">
                                <x-button type="submit"
                                          variant="primary"
                                          icon="bi-shield-plus"
                                          :tooltip="lexicon('tooltip_common_SaveRole', 'Save this user permission role')">
                                    {{ lexicon('btn_Roles_SaveRole', 'Save Role') }}
                                </x-button>
                            </div>
                        </x-meta>
                    </x-tab>

                    {{-- Manage System Permissions Panel --}}
                    <x-tab type="accordion"
                           :title="lexicon('admin_label_ManagePermissions', 'Manage System Permissions')"
                           icon="bi-key"
                           :open="false">
                        <div class="flex flex-col gap-md">
                            <x-meta type="default"
                                    :action="route('admin.access_clearance_roles.permissions.store')"
                                    method="POST"
                                    :loadingTitle="lexicon('msg_Roles_CreatingPermission', 'Creating Permission...')">
                                <div class="flex flex-row flex-wrap items-end gap-sm">
                                    <div class="flex-grow">
                                        <x-metadata type="text"
                                                    name="name"
                                                    id="create-permission-name"
                                                    :label="lexicon('label_Roles_PermissionName', 'Permission Token')"
                                                    :placeholder="lexicon('label_Roles_PermissionNamePlaceholder', 'e.g. view_drafts')"
                                                    :tooltip="lexicon('tooltip_Roles_PermNameInput', 'Enter permission code slug')"
                                                    :required="true" />
                                    </div>
                                    <x-button type="submit"
                                              variant="secondary"
                                              icon="bi-key"
                                              :tooltip="lexicon('tooltip_common_SavePermission', 'Create a new system permission')">
                                        + {{ lexicon('btn_Roles_CreatePermission', 'Create Permission') }}
                                    </x-button>
                                </div>
                            </x-meta>

                            <div class="w-full md-w-64">
                                <x-metadata type="search"
                                            name="permission_search"
                                            id="permission-search-input"
                                            :bare="true"
                                            xModel="permissionSearch"
                                            :placeholder="lexicon('label_Roles_FilterPermissionsPlaceholder', 'Search permissions by token...')"
                                            :tooltip="lexicon('tooltip_Roles_FilterPermissions', 'Instantaneously filter system permissions')" />
                            </div>

                            <x-table type="admin"
                                     :headers="[
                                         lexicon('label_common_Permission', 'Permission Token'),
                                         lexicon('label_common_Actions', 'Actions')
                                     ]"
                                     :emptyText="lexicon('msg_Roles_NoPermissionsFound', 'No permissions registered.')">
                                @foreach($allPermissions ?? [] as $perm)
                                    <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                        data-permission-row
                                        data-id="{{ $perm->id }}"
                                        data-name="{{ $perm->name }}"
                                        x-show="matchesPermissionSearch('{{ addslashes($perm->name) }}')">
                                        <td class="p-4 font-mono text-xs text-accent">
                                            <div class="flex flex-row items-center gap-xs">
                                                <span>{{ $perm->name }}</span>
                                                <x-button type="button"
                                                          variant="ghost"
                                                          size="sm"
                                                          icon="bi-clipboard"
                                                          :tooltip="lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')"
                                                          x-clipboard="'{{ addslashes($perm->name) }}'" />
                                            </div>
                                        </td>
                                        <td class="p-4 text-right">
                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_common_DeletePermission', 'Delete this permission')"
                                                      x-data="deleteAction('{{ route('admin.access_clearance_roles.permissions.destroy', $perm->id) }}', 'DELETE', '{{ addslashes($perm->name) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </td>
                                    </tr>
                                @endforeach
                            </x-table>

                            <script type="application/json" class="permissions-json-payload hidden">
                                {!! collect($allPermissions ?? [])->map(fn($p) => ['id' => (string) $p->id, 'name' => $p->name])->values()->toJson() !!}
                            </script>
                        </div>
                    </x-tab>
                </div>
            @endcan
        </x-slot:top>

        <x-slot:bottom>
            <div class="flex flex-col gap-lg">
                {{-- Existing Roles Panel --}}
                <x-tab type="accordion"
                       :title="lexicon('admin_label_ExistingRoles', 'Configured Access Clearance Roles')"
                       icon="bi-person-lock"
                       :open="true">
                    <x-slot:top>
                        <div @click.stop class="w-full md-w-64">
                            <x-metadata type="search"
                                        name="role_search"
                                        id="role-search-input"
                                        :bare="true"
                                        xModel="roleSearch"
                                        :placeholder="lexicon('label_Roles_FilterRolesPlaceholder', 'Search roles or permissions...')"
                                        :tooltip="lexicon('tooltip_Roles_FilterRoles', 'Instantaneously filter roles')" />
                        </div>
                    </x-slot:top>

                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_common_Name', 'Role Title'),
                                 lexicon('label_Roles_Permissions', 'Assigned Permissions'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_Roles_NoRolesFound', 'No roles discovered.')">
                        @foreach($roles ?? [] as $role)
                            @php
                                $rolePermIds = $role->permissions->pluck('id')->values()->all();
                                $rolePermNames = $role->permissions->pluck('name')->implode(' ');
                            @endphp
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-role-row
                                data-id="{{ $role->id }}"
                                data-name="{{ $role->name }}"
                                data-permissions="{{ $rolePermNames }}"
                                x-show="matchesRoleSearch('{{ addslashes($role->name) }}', '{{ addslashes($rolePermNames) }}')">
                                <td class="p-4 font-bold text-h">{{ ucfirst($role->name) }}</td>
                                <td class="p-4">
                                    <div class="flex flex-row flex-wrap gap-xs items-center">
                                        @if($role->name === 'webmaster')
                                            <x-badge type="pill"
                                                     variant="danger"
                                                     icon="bi-shield-fill-check"
                                                     :tooltip="lexicon('tooltip_Roles_PermissionOverride', 'Bypasses all permission checks globally')">
                                                {{ lexicon('msg_common_GOD', 'Global Override (Webmaster)') }}
                                            </x-badge>
                                        @else
                                            @forelse($role->permissions as $perm)
                                                <x-badge type="pill"
                                                         variant="primary"
                                                         :tooltip="lexicon('tooltip_Roles_AssignedPermissionBadge', 'Assigned role permission')">
                                                    {{ $perm->name }}
                                                </x-badge>
                                            @empty
                                                <span class="text-xs text-muted font-italic">{{ lexicon('msg_common_None', 'No Permissions Assigned') }}</span>
                                            @endforelse
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    @can('manage_roles')
                                        <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                            <x-button type="button"
                                                      variant="secondary"
                                                      size="sm"
                                                      icon="bi-pencil-square"
                                                      :tooltip="lexicon('tooltip_common_EditRole', 'Edit assigned role permissions')"
                                                      @click="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', {{ Js::from($rolePermIds) }})">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            @if($role->name !== 'webmaster')
                                                <x-button type="button"
                                                          variant="danger"
                                                          size="sm"
                                                          icon="bi-trash"
                                                          :tooltip="lexicon('tooltip_common_DeleteRole', 'Delete this role')"
                                                          x-data="deleteAction('{{ route('admin.access_clearance_roles.destroy', $role->id) }}', 'DELETE', '{{ addslashes($role->name) }}')"
                                                          @click="confirmDelete()">
                                                    {{ lexicon('btn_common_Delete', 'Delete') }}
                                                </x-button>
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-tab>

                {{-- Navigation Permission Audit Panel --}}
                <x-tab type="accordion"
                       :title="lexicon('admin_label_NavPermissionAudit', 'Table of Contents Clearance Audit')"
                       icon="bi-signpost-split"
                       :open="false">
                    <x-slot:top>
                        <div @click.stop class="w-full md-w-64">
                            <x-metadata type="search"
                                        name="audit_search"
                                        id="audit-search-input"
                                        :bare="true"
                                        xModel="auditSearch"
                                        :placeholder="lexicon('label_Roles_FilterNavPlaceholder', 'Search by token, URL, or permission...')"
                                        :tooltip="lexicon('tooltip_Roles_FilterNavAudit', 'Instantaneously filter navigation audit items')" />
                        </div>
                    </x-slot:top>

                    <p class="text-xs text-muted m-0">
                        {{ lexicon('msg_Roles_NavAuditDesc', 'Displays all Table of Contents menu entries across the portal and their required access clearance or permission token.') }}
                    </p>

                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_common_LexiconKey', 'Translation Token'),
                                 lexicon('label_common_url', 'Destination URL'),
                                 lexicon('label_Roles_Location', 'Display Location'),
                                 lexicon('label_Roles_Visibility', 'Access Clearance'),
                                 lexicon('label_common_Permission', 'Required Permission')
                             ]"
                             :emptyText="lexicon('msg_Roles_NoNavLinksFound', 'No navigation links discovered.')">
                        @foreach($navigationLinks ?? [] as $navLink)
                            @php
                                $navKey = $navLink->translation_code_key ?? $navLink->lexicon_key ?? '';
                                $navUrl = $navLink->destination_url ?? $navLink->url ?? '';
                                $navLoc = $navLink->display_location ?? $navLink->location ?? 'primary';
                                $navVis = $navLink->access_clearance ?? $navLink->visibility ?? 'public';
                                $navPerm = $navLink->required_permission ?? '';
                            @endphp
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-audit-row
                                x-show="matchesAuditSearch('{{ addslashes($navKey) }}', '{{ addslashes($navUrl) }}', '{{ addslashes($navLoc) }}', '{{ addslashes($navVis) }}', '{{ addslashes($navPerm) }}')">
                                <td class="p-4 font-mono text-xs text-h">
                                    <div class="flex flex-row items-center gap-xs">
                                        <span>{{ $navKey }}</span>
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-clipboard"
                                                  :tooltip="lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')"
                                                  x-clipboard="'{{ addslashes($navKey) }}'" />
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-xs text-muted">{{ $navUrl }}</td>
                                <td class="p-4">
                                    <x-badge type="pill" :variant="$navLoc === 'primary' ? 'success' : 'warning'">
                                        {{ ucfirst($navLoc) }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-sm text-p">{{ ucfirst($navVis) }}</td>
                                <td class="p-4">
                                    @if($navPerm)
                                        <x-badge type="pill" variant="primary">{{ $navPerm }}</x-badge>
                                    @else
                                        <span class="text-xs text-muted font-italic">{{ lexicon('label_Roles_NoneVisibilityBased', 'None (Clearance Based)') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-tab>
            </div>
        </x-slot:bottom>
    </x-grid-wrapper>

    {{-- MODAL: EDIT ROLE & PERMISSIONS --}}
    @can('manage_roles')
        <x-modal show="modals.role.open" onClose="closeEditModal()" maxWidth="max-w-lg">
            <x-slot:top>
                <span>{{ lexicon('label_Roles_EditRoleTitle', 'Edit Role & Permissions') }}</span>
            </x-slot:top>

            <x-meta type="modal"
                    :action="route('admin.access_clearance_roles.store')"
                    method="POST"
                    :loadingTitle="lexicon('msg_Roles_UpdatingRole', 'Updating Role...')">
                <input type="hidden" name="role_id" :value="modals.role.editRoleId">

                <x-metadata type="text"
                            name="name"
                            id="edit-role-name"
                            xModel="modals.role.editRoleName"
                            :label="lexicon('label_Roles_RoleName', 'Role Name')"
                            :placeholder="lexicon('label_Roles_RoleNamePlaceholder', 'e.g. senior gamemaster')"
                            :tooltip="lexicon('tooltip_Roles_RoleNameInput', 'Enter unique role name')"
                            ::readonly="modals.role.editRoleName === 'webmaster'"
                            :required="true" />

                <div class="flex flex-col gap-xs"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_Roles_AssignPermissionsSelect', 'Search and select one or more system permissions for this role')) }}'">
                    <label for="edit-role-permissions" class="text-xs font-bold text-input-muted uppercase text-wider">
                        {{ lexicon('label_Roles_AssignPermissions', 'Assign System Permissions') }}
                    </label>
                    <select id="edit-role-permissions"
                            name="permissions[]"
                            multiple
                            aria-label="{{ lexicon('label_Roles_AssignPermissions', 'Assign Permissions') }}"
                            class="w-full h-input p-input bg-input text-input border-input border-rounded text-sm"
                            placeholder="{{ lexicon('label_Roles_SelectPermissionsPlaceholder', 'Select permissions...') }}">
                        @foreach($allPermissions ?? [] as $permission)
                            <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                        @endforeach
                    </select>
                </div>

                <x-slot:footer>
                    <x-button type="button"
                              variant="secondary"
                              :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                              @click="closeEditModal()">
                        {{ lexicon('btn_common_Cancel', 'Cancel') }}
                    </x-button>

                    <x-button type="submit"
                              variant="primary"
                              icon="bi-check2-circle"
                              :tooltip="lexicon('tooltip_common_SaveRole', 'Save this user permission role')">
                        {{ lexicon('btn_common_SaveChanges', 'Save Changes') }}
                    </x-button>
                </x-slot:footer>
            </x-meta>
        </x-modal>
    @endcan
</div>
@endsection