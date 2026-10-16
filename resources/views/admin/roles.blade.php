@extends('layouts.app')

@section('title', lexicon('admin_label_roles', 'Permission & Roles Hub'))

@section('content')
<div x-data="roleManager" @ajax-success.window="handleAjaxSuccess()">
    <x-admin-grid-wrapper :page="$systemPage">

        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_roles', 'Permission & Roles Hub')" />

            @can('manage_roles')
                <div class="flex flex-col gap-lg">
                    {{-- Create Role Panel --}}
                    <x-accordion-panel :title="lexicon('admin_label_CreateEditRole', 'Create or Edit Role')" :expanded="false">
                        <form method="POST"
                              action="{{ route('admin.roles.store') }}"
                              x-data="ajaxForm"
                              @submit.prevent="submit"
                              data-loading-title="{{ lexicon('msg_Roles_SavingRole', 'Saving Role...') }}"
                              class="flex flex-col gap-md">
                            @csrf
                            <x-form-input name="name"
                                          id="create-role-name"
                                          :label="lexicon('label_Roles_RoleName', 'Role Name')"
                                          :placeholder="lexicon('label_Roles_RoleNamePlaceholder', 'e.g. senior user')"
                                          :tooltip="lexicon('tooltip_Roles_RoleNameInput', 'Enter unique role name')"
                                          :required="true" />

                            <div class="flex flex-col gap-xs"
                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Roles_AssignPermissionsSelect', 'Search and select one or more system permissions for this role')) }}'">
                                <label for="create-role-permissions" class="text-xs font-bold text-input-muted">
                                    <span>{{ lexicon('label_Roles_AssignPermissions', 'Assign Permissions') }}</span>
                                </label>
                                <select id="create-role-permissions"
                                        name="permissions[]"
                                        multiple
                                        aria-label="{{ lexicon('label_Roles_AssignPermissions', 'Assign Permissions') }}"
                                        class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                                        placeholder="{{ lexicon('label_Roles_SelectPermissionsPlaceholder', 'Select permissions...') }}">
                                    @foreach($allPermissions as $permission)
                                        <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                                <x-button type="submit"
                                          variant="primary"
                                          aria-label="{{ lexicon('tooltip_common_SaveRole', 'Save this user permission role') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_SaveRole', 'Save this user permission role')) }}'">
                                    <i class="bi bi-shield-plus mr-1"></i>
                                    <span>{{ lexicon('btn_Roles_SaveRole', 'Save Role') }}</span>
                                </x-button>
                            </div>
                        </form>
                    </x-accordion-panel>

                    {{-- Manage System Permissions Panel --}}
                    <x-accordion-panel :title="lexicon('admin_label_ManagePermissions', 'Manage System Permissions')" :expanded="false">
                        <div class="flex flex-col gap-md">
                            <form method="POST"
                                  action="{{ route('admin.permissions.store') }}"
                                  x-data="ajaxForm"
                                  @submit.prevent="submit"
                                  data-loading-title="{{ lexicon('msg_Roles_CreatingPermission', 'Creating Permission...') }}"
                                  class="flex flex-col gap-md">
                                @csrf
                                <x-form-input name="name"
                                              id="create-permission-name"
                                              :label="lexicon('label_Roles_PermissionName', 'Permission Code Name')"
                                              :placeholder="lexicon('label_Roles_PermissionNamePlaceholder', 'e.g. view_drafts')"
                                              :tooltip="lexicon('tooltip_Roles_PermNameInput', 'Enter permission code slug')"
                                              :required="true" />

                                <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                                    <x-button type="submit"
                                              variant="secondary"
                                              aria-label="{{ lexicon('tooltip_common_SavePermission', 'Create a new system permission') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_SavePermission', 'Create a new system permission')) }}'">
                                        <i class="bi bi-key mr-1"></i>
                                        <span>{{ lexicon('btn_Roles_CreatePermission', 'Create Permission') }}</span>
                                    </x-button>
                                </div>
                            </form>

                            <div class="w-full md-w-1-2 mt-2">
                                <x-form-input name="permission_search"
                                              id="permission-search-input"
                                              :label="lexicon('label_Roles_FilterPermissions', 'Filter System Permissions')"
                                              :placeholder="lexicon('label_Roles_FilterPermissionsPlaceholder', 'Search permissions by code name...')"
                                              :tooltip="lexicon('tooltip_Roles_FilterPermissions', 'Instantaneously filter system permissions using fuzzy search')"
                                              xModel="permissionSearch" />
                            </div>

                            <div class="mt-2">
                                <x-table>
                                    <x-slot:headers>
                                        <th class="p-4 text-left">{{ lexicon('label_common_Permission', 'Permission') }}</th>
                                        <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                                    </x-slot:headers>
                                    @forelse($allPermissions as $perm)
                                        <tr class="border-b-table-row hover-bg-table-row"
                                            x-show="matchesPermissionSearch('{{ addslashes($perm->name) }}')">
                                            <td class="p-4 font-mono text-xs text-p">
                                                <div class="flex flex-row items-center gap-xs">
                                                    <span>{{ $perm->name }}</span>
                                                    <x-button type="button"
                                                              variant="text"
                                                              class="text-xs"
                                                              x-clipboard="'{{ addslashes($perm->name) }}'"
                                                              aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                                        <i class="bi bi-clipboard"></i>
                                                    </x-button>
                                                </div>
                                            </td>
                                            <td class="p-4 text-right">
                                                <form method="POST"
                                                      action="{{ route('admin.permissions.destroy', $perm->id) }}"
                                                      x-data="ajaxForm"
                                                      @submit.prevent="submit"
                                                      class="inline-block m-0 p-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-button type="submit"
                                                              variant="danger"
                                                              class="p-btn-sm text-xs"
                                                              aria-label="{{ lexicon('tooltip_common_DeletePermission', 'Delete this permission') }}"
                                                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_DeletePermission', 'Delete this permission')) }}'">
                                                        <i class="bi bi-trash mr-1"></i>
                                                        <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                                    </x-button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="p-6 text-center text-muted italic">
                                                <span>{{ lexicon('msg_Roles_NoPermissionsFound', 'No permissions registered.') }}</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </x-table>
                                <script type="application/json" class="permissions-json-payload">
                                    {!! $allPermissions->map(fn($p) => ['id' => (string) $p->id, 'name' => $p->name])->values()->toJson() !!}
                                </script>
                            </div>
                        </div>
                    </x-accordion-panel>
                </div>
            @endcan
        </x-slot:top>

        <x-slot:bottom>
            <div class="flex flex-col gap-lg mt-6">
                {{-- Existing Roles Panel --}}
                <x-accordion-panel :title="lexicon('admin_label_ExistingRoles', 'Existing Roles')" :expanded="true">
                    <div class="flex flex-col gap-md">
                        <div class="w-full md-w-1-2">
                            <x-form-input name="role_search"
                                          id="role-search-input"
                                          :label="lexicon('label_Roles_FilterRoles', 'Filter Existing Roles')"
                                          :placeholder="lexicon('label_Roles_FilterRolesPlaceholder', 'Search by role name or assigned permission...')"
                                          :tooltip="lexicon('tooltip_Roles_FilterRoles', 'Instantaneously filter roles using fuzzy search')"
                                          xModel="roleSearch" />
                        </div>

                        <x-table>
                            <x-slot:headers>
                                <th class="p-4 text-left">{{ lexicon('label_common_Name', 'Name') }}</th>
                                <th class="p-4 text-left">{{ lexicon('label_Roles_Permissions', 'Permissions') }}</th>
                                <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                            </x-slot:headers>
                            @forelse($roles as $role)
                                @php
                                    $rolePermIds = $role->permissions->pluck('id')->values()->toJson();
                                    $rolePermNames = $role->permissions->pluck('name')->implode(' ');
                                @endphp
                                <tr class="border-b-table-row hover-bg-table-row"
                                    x-show="matchesRoleSearch('{{ addslashes($role->name) }}', '{{ addslashes($rolePermNames) }}')">
                                    <td class="p-4 font-bold text-p">
                                        <span>{{ ucfirst($role->name) }}</span>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-row flex-wrap gap-xs items-center">
                                            @if($role->name === 'webmaster')
                                                <x-badge variant="danger"
                                                         aria-label="{{ lexicon('tooltip_Roles_PermissionOverride', 'Bypasses all permission checks globally') }}"
                                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Roles_PermissionOverride', 'Bypasses all permission checks globally')) }}'">
                                                    <span>{{ lexicon('msg_common_GOD', 'You have permission override.') }}</span>
                                                </x-badge>
                                            @else
                                                @forelse($role->permissions as $perm)
                                                    <x-badge variant="primary"
                                                             aria-label="{{ $perm->name }}"
                                                             x-tooltip="'{{ addslashes(lexicon('tooltip_Roles_AssignedPermissionBadge', 'Assigned role permission')) }}'">
                                                        <span>{{ $perm->name }}</span>
                                                    </x-badge>
                                                @empty
                                                    <span class="text-xs text-muted italic">{{ lexicon('msg_common_None', 'No Permission Set!') }}</span>
                                                @endforelse
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        @can('manage_roles')
                                            <div class="flex flex-row flex-wrap items-center justify-end gap-sm text-sm">
                                                <x-button type="button"
                                                          variant="secondary"
                                                          class="p-btn-sm text-xs"
                                                          @click="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', {{ $rolePermIds }})"
                                                          aria-label="{{ lexicon('tooltip_common_EditRole', 'Edit assigned role permissions') }}"
                                                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_EditRole', 'Edit assigned role permissions')) }}'">
                                                    <i class="bi bi-pencil-square mr-1"></i>
                                                    <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                                </x-button>

                                                @if($role->name !== 'webmaster')
                                                    <form method="POST"
                                                          action="{{ route('admin.roles.destroy', $role->id) }}"
                                                          x-data="ajaxForm"
                                                          @submit.prevent="submit"
                                                          class="inline-block m-0 p-0">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-button type="submit"
                                                                  variant="danger"
                                                                  class="p-btn-sm text-xs"
                                                                  aria-label="{{ lexicon('tooltip_common_DeleteRole', 'Delete this role') }}"
                                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_DeleteRole', 'Delete this role')) }}'">
                                                            <i class="bi bi-trash mr-1"></i>
                                                            <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                                        </x-button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-muted italic">
                                        <span>{{ lexicon('msg_Roles_NoRolesFound', 'No roles discovered.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </x-table>
                    </div>
                </x-accordion-panel>

                {{-- Navigation Permission Audit Panel --}}
                <x-accordion-panel :title="lexicon('admin_label_NavPermissionAudit', 'Navigation Permission Audit')" :expanded="false">
                    <div class="flex flex-col gap-md">
                        <span class="text-xs text-muted block">
                            {{ lexicon('msg_Roles_NavAuditDesc', 'This table displays all navigation menu items across the station and the specific permission required to access them.') }}
                        </span>

                        <div class="w-full md-w-1-2">
                            <x-form-input name="audit_search"
                                          id="audit-search-input"
                                          :label="lexicon('label_Roles_FilterNavAudit', 'Filter Navigation Audit')"
                                          :placeholder="lexicon('label_Roles_FilterNavPlaceholder', 'Search by lexicon key, URL, or permission...')"
                                          :tooltip="lexicon('tooltip_Roles_FilterNavAudit', 'Instantaneously filter navigation audit items')"
                                          xModel="auditSearch" />
                        </div>

                        <x-table>
                            <x-slot:headers>
                                <th class="p-4 text-left">{{ lexicon('label_common_LexiconKey', 'Lexicon Key') }}</th>
                                <th class="p-4 text-left">{{ lexicon('label_common_url', 'URL Address') }}</th>
                                <th class="p-4 text-left">{{ lexicon('label_Roles_Location', 'Location') }}</th>
                                <th class="p-4 text-left">{{ lexicon('label_Roles_Visibility', 'Visibility') }}</th>
                                <th class="p-4 text-left">{{ lexicon('label_common_Permission', 'Required Permission') }}</th>
                            </x-slot:headers>
                            @forelse($navigationLinks as $navLink)
                                <tr class="border-b-table-row hover-bg-table-row"
                                    x-show="matchesAuditSearch('{{ addslashes($navLink->lexicon_key) }}', '{{ addslashes($navLink->url) }}', '{{ addslashes($navLink->location) }}', '{{ addslashes($navLink->visibility) }}', '{{ addslashes($navLink->required_permission ?? '') }}')">
                                    <td class="p-4 font-bold text-p font-mono text-xs">
                                        <div class="flex flex-row items-center gap-xs">
                                            <span>{{ $navLink->lexicon_key }}</span>
                                            <x-button type="button"
                                                      variant="text"
                                                      class="text-xs"
                                                      x-clipboard="'{{ addslashes($navLink->lexicon_key) }}'"
                                                      aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                                <i class="bi bi-clipboard"></i>
                                            </x-button>
                                        </div>
                                    </td>
                                    <td class="p-4 font-mono text-xs text-muted">
                                        <span>{{ $navLink->url }}</span>
                                    </td>
                                    <td class="p-4">
                                        <x-badge variant="{{ $navLink->location === 'primary' ? 'success' : 'warning' }}"
                                                 aria-label="{{ ucfirst($navLink->location) }}"
                                                 x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu location')) }}'">
                                            <span>{{ ucfirst($navLink->location) }}</span>
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-sm">
                                        <span>{{ ucfirst($navLink->visibility) }}</span>
                                    </td>
                                    <td class="p-4">
                                        @if($navLink->required_permission)
                                            <x-badge variant="primary"
                                                     aria-label="{{ $navLink->required_permission }}"
                                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_RequiredPermission', 'Restrict this link to users holding a specific permission')) }}'">
                                                <span class="font-mono">{{ $navLink->required_permission }}</span>
                                            </x-badge>
                                        @else
                                            <span class="text-xs text-muted italic">{{ lexicon('label_Roles_NoneVisibilityBased', 'None (Public / Visibility Based)') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-muted italic">
                                        <span>{{ lexicon('msg_Roles_NoNavLinksFound', 'No navigation links discovered.') }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </x-table>
                    </div>
                </x-accordion-panel>
            </div>
        </x-slot:bottom>

    </x-admin-grid-wrapper>

    {{-- Edit Role Pop-Out Modal --}}
    @can('manage_roles')
        <x-modal show="showEditModal" maxWidth="max-w-lg" onClose="closeEditModal()">
            <x-slot:top>
                <span>{{ lexicon('label_Roles_EditRoleTitle', 'Edit Role & Permissions') }}</span>
            </x-slot:top>

            <form id="edit-role-form"
                  method="POST"
                  action="{{ route('admin.roles.store') }}"
                  x-data="ajaxForm"
                  @submit.prevent="submit"
                  data-loading-title="{{ lexicon('msg_Roles_UpdatingRole', 'Updating Role...') }}"
                  class="flex flex-col gap-md">
                @csrf
                <input type="hidden" name="role_id" :value="editRoleId">

                <x-form-input name="name"
                              id="edit-role-name"
                              :label="lexicon('label_Roles_RoleName', 'Role Name')"
                              :placeholder="lexicon('label_Roles_RoleNamePlaceholder', 'e.g. senior user')"
                              :tooltip="lexicon('tooltip_Roles_RoleNameInput', 'Enter unique role name')"
                              xModel="editRoleName"
                              ::readonly="editRoleName === 'webmaster'"
                              :required="true" />

                <div class="flex flex-col gap-xs"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_Roles_AssignPermissionsSelect', 'Search and select one or more system permissions for this role')) }}'">
                    <label for="edit-role-permissions" class="text-xs font-bold text-input-muted">
                        <span>{{ lexicon('label_Roles_AssignPermissions', 'Assign Permissions') }}</span>
                    </label>
                    <select id="edit-role-permissions"
                            name="permissions[]"
                            multiple
                            aria-label="{{ lexicon('label_Roles_AssignPermissions', 'Assign Permissions') }}"
                            class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                            placeholder="{{ lexicon('label_Roles_SelectPermissionsPlaceholder', 'Select permissions...') }}">
                        @foreach($allPermissions as $permission)
                            <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t-actions pt-4 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                    <x-button type="button"
                              variant="secondary"
                              @click="closeEditModal()"
                              aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                        <span>{{ lexicon('btn_common_Cancel', 'Cancel') }}</span>
                    </x-button>
                    <x-button type="submit"
                              variant="primary"
                              aria-label="{{ lexicon('tooltip_common_SaveRole', 'Save this user permission role') }}"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_SaveRole', 'Save this user permission role')) }}'">
                        <i class="bi bi-check2-circle mr-1"></i>
                        <span>{{ lexicon('btn_common_SaveChanges', 'Save Changes') }}</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endcan
</div>
@endsection