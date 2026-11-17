{{-- resources/views/admin/table_of_contents.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_navigation', 'Table of Contents Manager'))

@section('content')
<div x-data="tableOfContentsManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="navigationTableContent"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_navigation', 'Table of Contents Manager')"
                           :subtitle="lexicon('desc_admin_navigation', 'Manage primary navigation links, nested dropdown hierarchies, context drawer groups, and access clearance gates.')"
                           icon="bi-list-nested">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="toc_search"
                                id="toc_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_Navigation_FilterPlaceholder', 'Search by token, URL, or group...')"
                                :tooltip="lexicon('tooltip_Navigation_FilterInput', 'Instantaneously filter navigation menu items using fuzzy search')" />

                    @can('manage_navigation')
                        <x-button variant="secondary"
                                  size="sm"
                                  href="{{ route('admin.table_of_contents.options') }}"
                                  icon="bi-sliders2"
                                  :tooltip="lexicon('tooltip_Navigation_ManageOptions', 'Configure custom navigation groups and parameters')">
                            {{ lexicon('btn_Navigation_Options', 'Parameters') }}
                        </x-button>

                        <x-button type="button"
                                  variant="success"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_Navigation_AddMenuItem', 'Create a new navigation menu link')"
                                  @click="openModal('create')">
                            + {{ lexicon('btn_Navigation_AddMenuItem', 'Add Menu Item') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Table of Contents Hierarchy --}}
            <x-tab type="accordion"
                   :title="lexicon('heading_Navigation_MenuTree', 'Configured Table of Contents Entries')"
                   icon="bi-signpost-2"
                   :open="true">
                <div x-ref="navigationTreeWrapper">
                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_common_SortOrder', 'Sort Order'),
                                 lexicon('label_common_group', 'Group'),
                                 lexicon('label_common_GroupSortOrder', 'Group Order'),
                                 lexicon('label_common_LexiconKey', 'Translation Token'),
                                 lexicon('label_common_url', 'Destination URL'),
                                 lexicon('label_nav_location', 'Display Location'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_Navigation_NoLinks', 'No navigation links found.')">
                        @foreach($entries ?? [] as $entry)
                            {{-- TOP LEVEL PARENT ROW --}}
                            <tr id="entry-row-{{ $entry->id }}"
                                data-search-item
                                data-navigation-item
                                data-id="{{ $entry->id }}"
                                data-translation_code_key="{{ $entry->translation_code_key }}"
                                data-destination_url="{{ $entry->destination_url }}"
                                data-group_title="{{ $entry->group_title ?? '' }}"
                                x-show="isItemVisible({{ $entry->id }})"
                                class="border-b-table-row hover-bg-panel-hover bg-panel-sub transition-colors">
                                <td class="p-4 font-bold text-h">{{ $entry->entry_sort_order }}</td>
                                <td class="p-4 font-bold text-p">{{ $entry->group_title ?: '—' }}</td>
                                <td class="p-4 font-mono text-xs text-muted">{{ $entry->group_sort_order }}</td>
                                <td class="p-4 font-bold text-h">
                                    <div class="flex flex-row flex-wrap items-center gap-xs">
                                        <span class="font-mono text-xs text-accent">{{ $entry->translation_code_key }}</span>
                                        @if(!$entry->is_visible)
                                            <x-badge type="pill"
                                                     variant="danger"
                                                     icon="bi-eye-slash"
                                                     :tooltip="lexicon('tooltip_common_HiddenFromSite', 'Hidden from live site')">
                                                {{ lexicon('label_common_Hidden', 'Hidden') }}
                                            </x-badge>
                                        @endif
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-clipboard"
                                                  :tooltip="lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')"
                                                  x-clipboard="'{{ addslashes($entry->translation_code_key) }}'" />
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-xs text-p">
                                    <div class="flex flex-row items-center gap-xs">
                                        <span>{{ $entry->destination_url }}</span>
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-clipboard"
                                                  :tooltip="lexicon('tooltip_Navigation_CopyUrl', 'Copy destination address to clipboard')"
                                                  x-clipboard="'{{ addslashes($entry->destination_url) }}'" />
                                    </div>
                                </td>
                                <td class="p-4">
                                    <x-badge type="pill"
                                             :variant="$entry->display_location === 'primary' ? 'success' : 'warning'"
                                             :tooltip="lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu display location')">
                                        {{ $entry->display_location === 'primary'
                                            ? lexicon('label_nav_primary', 'Main Nav')
                                            : lexicon('label_nav_drawer', 'Context Drawer') }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    @can('manage_navigation')
                                        <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-pencil"
                                                      :tooltip="lexicon('tooltip_Navigation_EditLink', 'Edit this menu item')"
                                                      @click="openModal('edit', {{ Js::from([
                                                          'id' => $entry->id,
                                                          'parent_entry_id' => '',
                                                          'group_title' => $entry->group_title ?? '',
                                                          'translation_code_key' => $entry->translation_code_key,
                                                          'destination_url' => $entry->destination_url,
                                                          'display_location' => $entry->display_location,
                                                          'access_clearance' => $entry->access_clearance,
                                                          'required_permission' => $entry->required_permission ?? '',
                                                          'window_target' => $entry->window_target,
                                                          'entry_sort_order' => $entry->entry_sort_order,
                                                          'group_sort_order' => $entry->group_sort_order ?? 0,
                                                          'is_visible' => (bool) $entry->is_visible,
                                                      ]) }})">
                                                {{ lexicon('btn_common_Edit', 'Edit') }}
                                            </x-button>

                                            <x-button type="button"
                                                      variant="danger"
                                                      size="sm"
                                                      icon="bi-trash"
                                                      :tooltip="lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item')"
                                                      x-data="deleteAction('/admin/table-of-contents/{{ $entry->id }}', 'DELETE', '{{ addslashes($entry->translation_code_key) }}', '{{ addslashes(lexicon('msg_Navigation_ConfirmDelete', 'Are you sure you want to remove this menu item?')) }}')"
                                                      @click="confirmDelete()">
                                                {{ lexicon('btn_common_Delete', 'Delete') }}
                                            </x-button>
                                        </div>
                                    @endcan
                                </td>
                            </tr>

                            {{-- NESTED CHILD ROWS --}}
                            @foreach($entry->children as $child)
                                <tr id="entry-row-{{ $child->id }}"
                                    data-search-item
                                    data-navigation-item
                                    data-id="{{ $child->id }}"
                                    data-parent_entry_id="{{ $entry->id }}"
                                    data-translation_code_key="{{ $child->translation_code_key }}"
                                    data-destination_url="{{ $child->destination_url }}"
                                    data-group_title="{{ $child->group_title ?? '' }}"
                                    x-show="isItemVisible({{ $child->id }})"
                                    class="border-b-table-row hover-bg-panel-hover transition-colors">
                                    <td class="p-4 text-muted">↳ {{ $child->entry_sort_order }}</td>
                                    <td class="p-4 text-muted">{{ $child->group_title ?: '—' }}</td>
                                    <td class="p-4 font-mono text-xs text-muted">{{ $child->group_sort_order }}</td>
                                    <td class="p-4">
                                        <div class="flex flex-row flex-wrap items-center gap-xs">
                                            <span class="font-mono text-xs text-accent">{{ $child->translation_code_key }}</span>
                                            @if(!$child->is_visible)
                                                <x-badge type="pill"
                                                         variant="danger"
                                                         icon="bi-eye-slash"
                                                         :tooltip="lexicon('tooltip_common_HiddenFromSite', 'Hidden from live site')">
                                                    {{ lexicon('label_common_Hidden', 'Hidden') }}
                                                </x-badge>
                                            @endif
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-clipboard"
                                                      :tooltip="lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')"
                                                      x-clipboard="'{{ addslashes($child->translation_code_key) }}'" />
                                        </div>
                                    </td>
                                    <td class="p-4 font-mono text-xs text-p">
                                        <div class="flex flex-row items-center gap-xs">
                                            <span>{{ $child->destination_url }}</span>
                                            <x-button type="button"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-clipboard"
                                                      :tooltip="lexicon('tooltip_Navigation_CopyUrl', 'Copy destination address to clipboard')"
                                                      x-clipboard="'{{ addslashes($child->destination_url) }}'" />
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <x-badge type="pill"
                                                 :variant="$child->display_location === 'primary' ? 'success' : 'warning'"
                                                 :tooltip="lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu display location')">
                                            {{ $child->display_location === 'primary'
                                                ? lexicon('label_nav_primary', 'Main Nav')
                                                : lexicon('label_nav_drawer', 'Context Drawer') }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        @can('manage_navigation')
                                            <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                                <x-button type="button"
                                                          variant="ghost"
                                                          size="sm"
                                                          icon="bi-pencil"
                                                          :tooltip="lexicon('tooltip_Navigation_EditLink', 'Edit this menu item')"
                                                          @click="openModal('edit', {{ Js::from([
                                                              'id' => $child->id,
                                                              'parent_entry_id' => (string) $child->parent_entry_id,
                                                              'group_title' => $child->group_title ?? '',
                                                              'translation_code_key' => $child->translation_code_key,
                                                              'destination_url' => $child->destination_url,
                                                              'display_location' => $child->display_location,
                                                              'access_clearance' => $child->access_clearance,
                                                              'required_permission' => $child->required_permission ?? '',
                                                              'window_target' => $child->window_target,
                                                              'entry_sort_order' => $child->entry_sort_order,
                                                              'group_sort_order' => $child->group_sort_order ?? 0,
                                                              'is_visible' => (bool) $child->is_visible,
                                                          ]) }})">
                                                    {{ lexicon('btn_common_Edit', 'Edit') }}
                                                </x-button>

                                                <x-button type="button"
                                                          variant="danger"
                                                          size="sm"
                                                          icon="bi-trash"
                                                          :tooltip="lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item')"
                                                          x-data="deleteAction('/admin/table-of-contents/{{ $child->id }}', 'DELETE', '{{ addslashes($child->translation_code_key) }}', '{{ addslashes(lexicon('msg_Navigation_ConfirmDelete', 'Are you sure you want to remove this menu item?')) }}')"
                                                          @click="confirmDelete()">
                                                    {{ lexicon('btn_common_Delete', 'Delete') }}
                                                </x-button>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </x-table>
                </div>
            </x-tab>

            {{-- MODAL: CREATE / EDIT MENU ENTRY --}}
            @can('manage_navigation')
                <x-modal show="modals.entry.open" onClose="closeModal()" maxWidth="4xl">
                    <x-slot:top>
                        <span x-text="modals.entry.mode === 'create'
                            ? '{{ addslashes(lexicon('label_nav_CreateLink', 'Add Menu Item')) }}'
                            : '{{ addslashes(lexicon('label_nav_EditLink', 'Edit Menu Item')) }}'"></span>
                    </x-slot:top>

                    <x-meta type="modal" xAction="modals.entry.action" xMethod="modals.entry.method">
                        <input type="hidden" name="is_visible" value="0">
                        <x-metadata type="checkbox"
                                    id="modal_is_visible"
                                    name="is_visible"
                                    value="1"
                                    xModel="modals.entry.is_visible"
                                    :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')" />

                        <div x-ref="tocModalDropdowns" class="flex flex-col gap-md">
                            <x-tab type="sub">
                                <div class="grid grid-cols-1 lg-grid-cols-3 gap-md">
                                    <x-metadata type="select"
                                                id="modal_parent_entry_id"
                                                name="parent_entry_id"
                                                xModel="modals.entry.parent_entry_id"
                                                :label="lexicon('label_nav_ParentLink', 'Parent Entry (Optional Dropdown)')"
                                                :tooltip="lexicon('tooltip_Navigation_ParentLink', 'Assign under a top-level main nav link to create a dropdown item')">
                                        <option value="">— {{ lexicon('label_common_None', 'Top Level') }} —</option>
                                        @foreach($parentOptions ?? [] as $parent)
                                            <option value="{{ $parent->id }}">{{ lexicon($parent->translation_code_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$parent->translation_code_key))) }}</option>
                                        @endforeach
                                    </x-metadata>

                                    <x-metadata type="select"
                                                id="modal_group_title"
                                                name="group_title"
                                                xModel="modals.entry.group_title"
                                                :label="lexicon('label_nav_group', 'Group Title')"
                                                :tooltip="lexicon('tooltip_Navigation_GroupName', 'Assign this link to a collapsible drawer group')"
                                                @change="
                                                    let selected = $el.options[$el.selectedIndex];
                                                    if (selected && selected.dataset.sortOrder) {
                                                        modals.entry.group_sort_order = selected.dataset.sortOrder;
                                                    }
                                                ">
                                        <option value="">— {{ lexicon('label_common_None', 'None') }} —</option>
                                        @foreach($groupOptions ?? [] as $opt)
                                            <option value="{{ $opt->parameter_value }}" data-sort-order="{{ $opt->parameter_sort_order }}">{{ lexicon($opt->parameter_label,$opt->parameter_value) }}</option>
                                        @endforeach
                                    </x-metadata>

                                    <x-metadata type="number"
                                                name="group_sort_order"
                                                id="modal_group_sort_order"
                                                xModel="modals.entry.group_sort_order"
                                                :label="lexicon('label_common_GroupSortOrder', 'Group Sort Order')"
                                                :tooltip="lexicon('tooltip_Navigation_GroupSortOrder', 'Numeric display sort order for the group')" />

                                    <x-metadata type="text"
                                                name="translation_code_key"
                                                id="modal_translation_code_key"
                                                xModel="modals.entry.translation_code_key"
                                                :label="lexicon('label_nav_LexiconKey', 'Translation Token')"
                                                :tooltip="lexicon('tooltip_Navigation_LexiconKey', 'Translation code key for this menu item')"
                                                :required="true" />

                                    <x-metadata type="text"
                                                name="destination_url"
                                                id="modal_destination_url"
                                                xModel="modals.entry.destination_url"
                                                :label="lexicon('label_nav_url', 'Destination URL')"
                                                :tooltip="lexicon('tooltip_Navigation_Url', 'Destination path or full address')"
                                                :required="true" />

                                    <x-metadata type="number"
                                                name="entry_sort_order"
                                                id="modal_entry_sort_order"
                                                xModel="modals.entry.entry_sort_order"
                                                :label="lexicon('label_sort_order', 'Entry Sort Order')"
                                                :tooltip="lexicon('tooltip_Navigation_SortOrder', 'Numeric display sort order within its group or bar')"
                                                :required="true" />
                                </div>
                            </x-tab>

                            <x-tab type="sub">
                                <div class="grid grid-cols-1 lg-grid-cols-3 gap-md">
                                    <x-metadata type="select"
                                                id="modal_display_location"
                                                name="display_location"
                                                xModel="modals.entry.display_location"
                                                :label="lexicon('label_nav_location', 'Display Location')"
                                                :tooltip="lexicon('tooltip_Navigation_Location', 'Display in the Main Nav bar or Context Drawer')">
                                        @foreach($locationOptions ?? [] as $opt)
                                            <option value="{{ $opt->parameter_value }}">{{ lexicon($opt->parameter_label,$opt->parameter_value) }}</option>
                                        @endforeach
                                    </x-metadata>

                                    <x-metadata type="select"
                                                id="modal_access_clearance"
                                                name="access_clearance"
                                                xModel="modals.entry.access_clearance"
                                                :label="lexicon('label_vis_title', 'Access Clearance')"
                                                :tooltip="lexicon('tooltip_Navigation_Visibility', 'Minimum user access clearance required to view this link')">
                                        @foreach($visibilityOptions ?? [] as $opt)
                                            <option value="{{ $opt->parameter_value }}">{{ lexicon($opt->parameter_label,$opt->parameter_value) }}</option>
                                        @endforeach
                                    </x-metadata>

                                    <x-metadata type="select"
                                                id="modal_window_target"
                                                name="window_target"
                                                xModel="modals.entry.window_target"
                                                :label="lexicon('label_target_WindowTarget', 'Window Target')"
                                                :tooltip="lexicon('tooltip_Navigation_WindowTarget', 'Open link in the same window or a new tab')">
                                        <option value="_self">{{ lexicon('label_target_self', 'Same Window (_self)') }}</option>
                                        <option value="_blank">{{ lexicon('label_target_blank', 'New Tab (_blank)') }}</option>
                                    </x-metadata>
                                </div>
                            </x-tab>

                            <x-metadata type="select"
                                        id="modal_required_permission"
                                        name="required_permission"
                                        xModel="modals.entry.required_permission"
                                        :label="lexicon('label_PageBuilder_RequiredPermission', 'Required Spatie Permission')"
                                        :tooltip="lexicon('tooltip_Navigation_RequiredPermission', 'Restrict this link to users holding a specific permission')">
                                <option value="">— {{ lexicon('label_Roles_NoneVisibilityBased', 'None (Public / Access Clearance Based)') }} —</option>
                                @foreach($permissions ?? [] as $perm)
                                    <option value="{{ $perm->name }}">{{ $perm->name }}</option>
                                @endforeach
                            </x-metadata>
                        </div>

                        <x-slot:footer>
                            <x-button type="button"
                                      variant="secondary"
                                      :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                      @click="closeModal()">
                                {{ lexicon('btn_common_Cancel', 'Cancel') }}
                            </x-button>

                            <x-button type="submit"
                                      variant="primary"
                                      icon="bi-save"
                                      :tooltip="lexicon('tooltip_Navigation_SaveLink', 'Save navigation menu item')">
                                <span x-text="modals.entry.mode === 'create'
                                    ? '{{ addslashes(lexicon('btn_nav_SaveLink', 'Save Menu Item')) }}'
                                    : '{{ addslashes(lexicon('btn_nav_UpdateLink', 'Update Menu Item')) }}'"></span>
                            </x-button>
                        </x-slot:footer>
                    </x-meta>
                </x-modal>
            @endcan
        </x-slot:content>
    </x-grid-wrapper>
</div>
@endsection