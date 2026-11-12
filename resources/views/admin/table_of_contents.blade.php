@extends('layouts.app')
@section('title', lexicon('admin_label_navigation', 'Navigation Manager'))

@section('content')
<div x-data="tableOfContentsManager" @ajax-success.window="refreshDOM()">
    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_navigation', 'Navigation Manager')">
                <x-slot:actions>
                    @can('manage_navigation')
                        <x-button variant="success"
                                  href="/admin/navigation/options"
                                  aria-label="{{ lexicon('tooltip_Navigation_ManageOptions', 'Configure navigation locations, groups, and access clearance options') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_ManageOptions', 'Configure navigation locations, groups, and access clearance options')) }}'">
                            <i class="bi bi-sliders"></i> {{ lexicon('btn_Navigation_ManageOptions', 'Manage Dropdown Options') }}
                        </x-button>

                        <x-button type="button"
                                  variant="success"
                                  @click="openModal('create')"
                                  aria-label="{{ lexicon('tooltip_Navigation_AddMenuItem', 'Create a new navigation menu link') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_AddMenuItem', 'Create a new navigation menu link')) }}'">
                            <i class="bi bi-plus-lg"></i> {{ lexicon('btn_Navigation_AddMenuItem', 'Add Menu Item') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <!-- Instant Client-Side Fuzzy Search (Fuse.js) -->
            <div class="bg-panel border border-panel rounded-panel shadow-panel p-4 mb-4">
                <x-form-input name="toc_search"
                              id="toc_search"
                              :label="lexicon('label_Navigation_FilterLinks', 'Filter Navigation Links')"
                              :placeholder="lexicon('label_Navigation_FilterPlaceholder', 'Search by translation code key, destination address, group, or display location...')"
                              :tooltip="lexicon('tooltip_Navigation_FilterInput', 'Instantaneously filter navigation menu items using fuzzy search')"
                              xModel="searchQuery" />
            </div>

            <!-- AJAX Swappable Container for Table of Contents Entries -->
            <x-table x-ref="tocTableContent" class="overflow-hidden">
                <x-slot:headers>
                    <th class="p-3">{{ lexicon('label_common_SortOrder', 'Sort Order') }}</th>
                    <th class="p-3">{{ lexicon('label_common_group', 'Group') }}</th>
                    <th class="p-3">{{ lexicon('label_common_GroupSortOrder', 'Group Sort Order') }}</th>
                    <th class="p-3">{{ lexicon('label_common_LexiconKey', 'Translation Code Key') }}</th>
                    <th class="p-3">{{ lexicon('label_common_url', 'Destination Address') }}</th>
                    <th class="p-3">{{ lexicon('label_nav_location', 'Display Location') }}</th>
                    <th class="p-3 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                </x-slot:headers>

                @forelse($entries ?? $links as$entry)
                    <!-- TOP LEVEL PARENT ROW -->
                    <tr id="entry-row-{{ $entry->id }}"
                        data-search-item
                        data-id="{{ $entry->id }}"
                        data-translation-code-key="{{ $entry->translation_code_key }}"
                        data-destination-url="{{ $entry->destination_url }}"
                        data-group-title="{{ $entry->group_title ?? '' }}"
                        data-display-location="{{ $entry->display_location }}"
                        x-show="isItemVisible({{ $entry->id }}, {{ json_encode($entry->children->pluck('id')->toArray()) }})"
                        class="border-b-table-row hover-bg-table-row bg-panel-sub">
                        <td class="p-3 font-bold">{{ $entry->entry_sort_order }}</td>
                        <td class="p-3 font-bold">{{ $entry->group_title ?? '—' }}</td>
                        <td class="p-3 font-bold">{{ $entry->group_sort_order }}</td>
                        <td class="p-3 font-bold">
                            <div class="flex flex-row items-center gap-xs">
                                <span>{{ $entry->translation_code_key }}</span>
                                @if(!$entry->is_visible)
                                    <x-badge variant="danger"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_common_HiddenFromSite', 'Hidden from live site')) }}'">
                                        <i class="bi bi-eye-slash"></i> {{ lexicon('label_common_Hidden', 'Hidden') }}
                                    </x-badge>
                                @endif
                                <x-button type="button"
                                          variant="text"
                                          class="text-xs text-muted hover-text-accent"
                                          x-clipboard="'{{ addslashes($entry->translation_code_key) }}'"
                                          aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                    <i class="bi bi-clipboard"></i>
                                </x-button>
                            </div>
                        </td>
                        <td class="p-3 font-mono text-xs">
                            <div class="flex flex-row items-center gap-xs">
                                <span>{{ $entry->destination_url }}</span>
                                <x-button type="button"
                                          variant="text"
                                          class="text-xs text-muted hover-text-accent"
                                          x-clipboard="'{{ addslashes($entry->destination_url) }}'"
                                          aria-label="{{ lexicon('tooltip_Navigation_CopyUrl', 'Copy destination address to clipboard') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_CopyUrl', 'Copy destination address to clipboard')) }}'">
                                    <i class="bi bi-clipboard"></i>
                                </x-button>
                            </div>
                        </td>
                        <td class="p-3">
                            <x-badge variant="{{ $entry->display_location === 'primary' ? 'success' : 'warning' }}"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu display location')) }}'">
                                {{ $entry->display_location === 'primary' ? lexicon('label_nav_primary', 'Main Nav') : lexicon('label_nav_drawer', 'Context Drawer') }}
                            </x-badge>
                        </td>
                        <td class="p-3 text-right">
                            @can('manage_navigation')
                                <div class="flex flex-row flex-wrap gap-sm items-center justify-end">
                                    <x-button type="button"
                                              variant="text"
                                              class="text-warning hover-warning text-xs"
                                              aria-label="{{ lexicon('tooltip_Navigation_EditLink', 'Edit this menu item') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_EditLink', 'Edit this menu item')) }}'"
                                              @click="openModal('edit', { 
                                                id: {{ $entry->id }}, 
                                                parent_entry_id: '',
                                                group_title: '{{ addslashes($entry->group_title) }}', 
                                                translation_code_key: '{{ addslashes($entry->translation_code_key) }}', 
                                                destination_url: '{{ addslashes($entry->destination_url) }}', 
                                                display_location: '{{ $entry->display_location }}', 
                                                access_clearance: '{{ $entry->access_clearance }}',
                                                required_permission: '{{ $entry->required_permission ?? '' }}', 
                                                window_target: '{{ $entry->window_target }}', 
                                                entry_sort_order: {{ $entry->entry_sort_order }}, 
                                                group_sort_order: {{ $entry->group_sort_order ?? 0 }},
                                                is_visible: {{ $entry->is_visible ? 'true' : 'false' }}
                                            })">
                                        <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                    </x-button>
                                    <x-button type="button"
                                              variant="text"
                                              class="text-danger hover-danger text-xs"
                                              aria-label="{{ lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item')) }}'"
                                              x-data="deleteAction(`/admin/navigation/{{ $entry->id }}`, 'DELETE', '{{ addslashes($entry->translation_code_key) }}', '{{ addslashes(lexicon('msg_Navigation_ConfirmDelete', 'Are you sure you want to remove this menu item?')) }}')"
                                              @click="confirmDelete()">
                                        <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                </div>
                            @endcan
                        </td>
                    </tr>

                    <!-- NESTED CHILD ROWS -->
                    @foreach($entry->children as$child)
                        <tr id="entry-row-{{ $child->id }}"
                            data-search-item
                            data-id="{{ $child->id }}"
                            data-parent-entry-id="{{ $entry->id }}"
                            data-translation-code-key="{{ $child->translation_code_key }}"
                            data-destination-url="{{ $child->destination_url }}"
                            data-group-title="{{ $child->group_title ?? '' }}"
                            data-display-location="{{ $child->display_location }}"
                            x-show="isItemVisible({{ $child->id }}, [{{$entry->id }}])"
                            class="border-b-table-row hover-bg-table-row">
                            <td class="p-3 pl-6 text-muted">↳ {{ $child->entry_sort_order }}</td>
                            <td class="p-3 text-muted">{{ $child->group_title ?? '—' }}</td>
                            <td class="p-3 text-muted">{{ $child->group_sort_order }}</td>
                            <td class="p-3">
                                <div class="flex flex-row items-center gap-xs">
                                    <span>{{ $child->translation_code_key }}</span>
                                    @if(!$child->is_visible)
                                        <x-badge variant="danger"
                                                 x-tooltip="'{{ addslashes(lexicon('tooltip_common_HiddenFromSite', 'Hidden from live site')) }}'">
                                            <i class="bi bi-eye-slash"></i> {{ lexicon('label_common_Hidden', 'Hidden') }}
                                        </x-badge>
                                    @endif
                                    <x-button type="button"
                                              variant="text"
                                              class="text-xs text-muted hover-text-accent"
                                              x-clipboard="'{{ addslashes($child->translation_code_key) }}'"
                                              aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                        <i class="bi bi-clipboard"></i>
                                    </x-button>
                                </div>
                            </td>
                            <td class="p-3 font-mono text-xs">
                                <div class="flex flex-row items-center gap-xs">
                                    <span>{{ $child->destination_url }}</span>
                                    <x-button type="button"
                                              variant="text"
                                              class="text-xs text-muted hover-text-accent"
                                              x-clipboard="'{{ addslashes($child->destination_url) }}'"
                                              aria-label="{{ lexicon('tooltip_Navigation_CopyUrl', 'Copy destination address to clipboard') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_CopyUrl', 'Copy destination address to clipboard')) }}'">
                                        <i class="bi bi-clipboard"></i>
                                    </x-button>
                                </div>
                            </td>
                            <td class="p-3">
                                <x-badge variant="{{ $child->display_location === 'primary' ? 'success' : 'warning' }}"
                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu display location')) }}'">
                                    {{ $child->display_location === 'primary' ? lexicon('label_nav_primary', 'Main Nav') : lexicon('label_nav_drawer', 'Context Drawer') }}
                                </x-badge>
                            </td>
                            <td class="p-3 text-right">
                                @can('manage_navigation')
                                    <div class="flex flex-row flex-wrap gap-sm items-center justify-end">
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-warning hover-warning text-xs"
                                                  aria-label="{{ lexicon('tooltip_Navigation_EditLink', 'Edit this menu item') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_EditLink', 'Edit this menu item')) }}'"
                                                  @click="openModal('edit', { 
                                                    id: {{ $child->id }}, 
                                                    parent_entry_id: '{{ $child->parent_entry_id }}',
                                                    group_title: '{{ addslashes($child->group_title) }}', 
                                                    translation_code_key: '{{ addslashes($child->translation_code_key) }}', 
                                                    destination_url: '{{ addslashes($child->destination_url) }}', 
                                                    display_location: '{{ $child->display_location }}', 
                                                    access_clearance: '{{ $child->access_clearance }}',
                                                    required_permission: '{{ $child->required_permission ?? '' }}', 
                                                    window_target: '{{ $child->window_target }}', 
                                                    entry_sort_order: {{ $child->entry_sort_order }}, 
                                                    group_sort_order: {{ $child->group_sort_order ?? 0 }},
                                                    is_visible: {{ $child->is_visible ? 'true' : 'false' }}
                                                })">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-danger hover-danger text-xs"
                                                  aria-label="{{ lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item')) }}'"
                                                  x-data="deleteAction(`/admin/navigation/{{ $child->id }}`, 'DELETE', '{{ addslashes($child->translation_code_key) }}', '{{ addslashes(lexicon('msg_Navigation_ConfirmDelete', 'Are you sure you want to remove this menu item?')) }}')"
                                                  @click="confirmDelete()">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-muted italic">{{ lexicon('msg_Navigation_NoLinks', 'No navigation links found.') }}</td>
                    </tr>
                @endforelse
            </x-table>

            <!-- ============================================== -->
            <!-- MODAL: ENTRY ADD / EDIT -->
            <!-- ============================================== -->

            @can('manage_navigation')
                <x-modal show="isOpen" maxWidth="max-w-4xl" onClose="closeModal()">
                    <x-slot:top>
                        <span x-text="mode === 'create' ? '{{ addslashes(lexicon('label_nav_CreateLink', 'Add Menu Item')) }}' : '{{ addslashes(lexicon('label_nav_EditLink', 'Edit Menu Item')) }}'"></span>
                    </x-slot:top>

                    <form :action="formAction"
                          method="POST"
                          class="flex flex-col gap-md"
                          x-data="ajaxForm"
                          @submit.prevent="submit"
                          data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                        @csrf
                        <input type="hidden" name="_method" :value="method">
                        <div class="flex flex-row items-center gap-sm mt-3">
                            <input type="hidden" name="is_visible" value="0">
                            <input type="checkbox"
                                   id="modal_is_visible"
                                   name="is_visible"
                                   value="1"
                                   x-model="is_visible"
                                   class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                            <label for="modal_is_visible" class="text-sm font-bold text-muted cursor-pointer">
                                {{ lexicon('label_common_ShowOnSite', 'Show on Site') }}
                            </label>
                        </div>
                        <div x-ref="tocModalDropdowns" class="flex flex-col gap-md">
                            <div class="grid grid-cols-1 gap-sm w-full lg-grid-cols-3 bg-panel border border-panel rounded-panel shadow-panel p-3">
                                <div class="flex flex-col gap-xs">
                                    <label for="modal_parent_entry_id" class="text-xs font-bold text-input-muted">{{ lexicon('label_nav_ParentLink', 'Parent Entry (Optional Dropdown)') }}</label>
                                    <select id="modal_parent_entry_id"
                                            name="parent_entry_id"
                                            x-model="parent_entry_id"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_nav_ParentLink', 'Parent Entry (Optional Dropdown)') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_ParentLink', 'Assign under a top-level main nav link to create a dropdown item')) }}'">
                                        <option value="">— {{ lexicon('label_common_None', 'Top Level') }} —</option>
                                        @foreach($parentOptions as$parent)
                                            <option value="{{ $parent->id }}">{{ lexicon($parent->translation_code_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$parent->translation_code_key))) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label for="modal_group_title" class="text-xs font-bold text-input-muted">{{ lexicon('label_nav_group', 'Group Title') }}</label>
                                    <select id="modal_group_title"
                                            name="group_title"
                                            x-model="group_title"
                                            @change="
                                                let selected = $el.options[$el.selectedIndex];
                                                if (selected && selected.dataset.sortOrder) {
                                                    group_sort_order = selected.dataset.sortOrder;
                                                }
                                            "
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_nav_group', 'Group Title') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_GroupName', 'Assign this link to a collapsible drawer group')) }}'">
                                        <option value="">— {{ lexicon('label_common_None', 'None') }} —</option>
                                        @foreach($groupOptions as$opt)
                                            <option value="{{ $opt->parameter_value }}" data-sort-order="{{ $opt->parameter_sort_order }}">{{ lexicon($opt->parameter_label,$opt->parameter_value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <x-form-input name="group_sort_order"
                                              id="modal_group_sort_order"
                                              type="number"
                                              :label="lexicon('label_common_GroupSortOrder', 'Group Sort Order')"
                                              :tooltip="lexicon('tooltip_Navigation_GroupSortOrder', 'Numeric display sort order for the group')"
                                              xModel="group_sort_order" />

                                <x-form-input name="translation_code_key"
                                              id="modal_translation_code_key"
                                              type="text"
                                              :label="lexicon('label_nav_LexiconKey', 'Translation Code Key')"
                                              :tooltip="lexicon('tooltip_Navigation_LexiconKey', 'Translation code key for this menu item')"
                                              xModel="translation_code_key"
                                              :required="true" />

                                <x-form-input name="destination_url"
                                              id="modal_destination_url"
                                              type="text"
                                              :label="lexicon('label_nav_url', 'Destination Address')"
                                              :tooltip="lexicon('tooltip_Navigation_Url', 'Destination path or full address')"
                                              xModel="destination_url"
                                              :required="true" />

                                <x-form-input name="entry_sort_order"
                                              id="modal_entry_sort_order"
                                              type="number"
                                              :label="lexicon('label_sort_order', 'Entry Sort Order')"
                                              :tooltip="lexicon('tooltip_Navigation_SortOrder', 'Numeric display sort order within its group or bar')"
                                              xModel="entry_sort_order"
                                              :required="true" />
                            </div>

                            <div class="grid grid-cols-1 gap-sm w-full lg-grid-cols-3 bg-panel border border-panel rounded-panel shadow-panel p-3">
                                <div class="flex flex-col gap-xs">
                                    <label for="modal_display_location" class="text-xs font-bold text-input-muted">{{ lexicon('label_nav_location', 'Display Location') }}</label>
                                    <select id="modal_display_location"
                                            name="display_location"
                                            x-model="display_location"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_nav_location', 'Display Location') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_Location', 'Display in the Main Nav bar or Context Drawer')) }}'">
                                        @foreach($locationOptions as$opt)
                                            <option value="{{ $opt->parameter_value }}">{{ lexicon($opt->parameter_label,$opt->parameter_value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label for="modal_access_clearance" class="text-xs font-bold text-input-muted">{{ lexicon('label_vis_title', 'Access Clearance') }}</label>
                                    <select id="modal_access_clearance"
                                            name="access_clearance"
                                            x-model="access_clearance"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_vis_title', 'Access Clearance') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_Visibility', 'Minimum user access clearance required to view this link')) }}'">
                                        @foreach($visibilityOptions as$opt)
                                            <option value="{{ $opt->parameter_value }}">{{ lexicon($opt->parameter_label,$opt->parameter_value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label for="modal_window_target" class="text-xs font-bold text-input-muted">{{ lexicon('label_target_WindowTarget', 'Window Target') }}</label>
                                    <select id="modal_window_target"
                                            name="window_target"
                                            x-model="window_target"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_target_WindowTarget', 'Window Target') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_WindowTarget', 'Open link in the same window or a new tab')) }}'">
                                        <option value="_self">{{ lexicon('label_target_self', 'Same Window (_self)') }}</option>
                                        <option value="_blank">{{ lexicon('label_target_blank', 'New Tab (_blank)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label for="modal_required_permission" class="text-xs font-bold text-input-muted">{{ lexicon('label_PageBuilder_RequiredPermission', 'Required Permission') }}</label>
                                <select id="modal_required_permission"
                                        name="required_permission"
                                        x-model="required_permission"
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                        aria-label="{{ lexicon('label_PageBuilder_RequiredPermission', 'Required Permission') }}"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_RequiredPermission', 'Restrict this link to users holding a specific permission')) }}'">
                                    <option value="">— {{ lexicon('label_Roles_NoneVisibilityBased', 'None (Public / Access Clearance Based)') }} —</option>
                                    @foreach($permissions as$perm)
                                        <option value="{{ $perm->name }}">{{ $perm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="border-t-actions pt-6 text-right mt-4">
                            <x-button type="submit"
                                      variant="primary"
                                      aria-label="{{ lexicon('tooltip_Navigation_SaveLink', 'Save navigation menu item') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_SaveLink', 'Save navigation menu item')) }}'">
                                <span x-text="mode === 'create' ? '{{ addslashes(lexicon('btn_nav_SaveLink', 'Save Menu Item')) }}' : '{{ addslashes(lexicon('btn_nav_UpdateLink', 'Update Menu Item')) }}'"></span>
                            </x-button>
                        </div>
                    </form>
                </x-modal>
            @endcan
        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection