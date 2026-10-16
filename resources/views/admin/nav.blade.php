@extends('layouts.app')
@section('title', lexicon('admin_label_navigation', 'Navigation Manager'))

@section('content')
<div x-data="navigationManager" @ajax-success.window="refreshDOM()">
    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_navigation', 'Navigation Manager')">
                <x-slot:actions>
                    @can('manage_navigation')
                        <x-button variant="success"
                                  href="/admin/navigation/options"
                                  aria-label="{{ lexicon('tooltip_Navigation_ManageOptions', 'Configure navigation locations, groups, and visibility options') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_ManageOptions', 'Configure navigation locations, groups, and visibility options')) }}'">
                            <i class="bi bi-sliders"></i> {{ lexicon('btn_Navigation_ManageOptions', 'Manage Dropdown Options') }}
                        </x-button>

                        <x-button type="button"
                                  variant="success"
                                  @click="openLinkModal('create')"
                                  aria-label="{{ lexicon('tooltip_Navigation_AddMenuItem', 'Create a new navigation menu link') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_AddMenuItem', 'Create a new navigation menu link')) }}'">
                            <i class="bi bi-plus-lg"></i> {{ lexicon('btn_Navigation_AddMenuItem', 'Add Menu Item') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            <!-- Instant Client-Side Fuzzy Search (Fuse.js) -->
            <div class="bg-panel border border-panel rounded-panel shadow-panel p-4 mb-4">
                <x-form-input name="nav_search"
                              id="nav_search"
                              :label="lexicon('label_Navigation_FilterLinks', 'Filter Navigation Links')"
                              :placeholder="lexicon('label_Navigation_FilterPlaceholder', 'Search by lexicon key, URL, group, or location...')"
                              :tooltip="lexicon('tooltip_Navigation_FilterInput', 'Instantaneously filter navigation menu items using fuzzy search')"
                              xModel="searchQuery" />
            </div>

            <!-- AJAX Swappable Container for Navigation Links -->
            <x-table x-ref="navTableContent" class="overflow-hidden">
                <x-slot:headers>
                    <th class="p-3">{{ lexicon('label_common_SortOrder', 'Sort Order') }}</th>
                    <th class="p-3">{{ lexicon('label_common_group', 'Group') }}</th>
                    <th class="p-3">{{ lexicon('label_common_GroupSortOrder', 'Group Sort Order') }}</th>
                    <th class="p-3">{{ lexicon('label_common_LexiconKey', 'Lexicon Key') }}</th>
                    <th class="p-3">{{ lexicon('label_common_url', 'URL Address') }}</th>
                    <th class="p-3">{{ lexicon('label_nav_location', 'Location') }}</th>
                    <th class="p-3 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                </x-slot:headers>

                @forelse($links as $link)
                    <!-- TOP LEVEL PARENT ROW -->
                    <tr id="link-row-{{ $link->id }}"
                        data-nav-search-item
                        data-id="{{ $link->id }}"
                        data-lexicon-key="{{ $link->lexicon_key }}"
                        data-url="{{ $link->url }}"
                        data-group-name="{{ $link->group_name ?? '' }}"
                        data-location="{{ $link->location }}"
                        x-show="isLinkVisible({{ $link->id }}, {{ json_encode($link->children->pluck('id')->toArray()) }})"
                        class="border-b-table-row hover-bg-table-row bg-panel-sub">
                        <td class="p-3 font-bold">{{ $link->order }}</td>
                        <td class="p-3 font-bold">{{ $link->group_name ?? '—' }}</td>
                        <td class="p-3 font-bold">{{ $link->group_order }}</td>
                        <td class="p-3 font-bold">
                            <div class="flex flex-row items-center gap-xs">
                                <span>{{ $link->lexicon_key }}</span>
                                <x-button type="button"
                                          variant="text"
                                          class="text-xs text-muted hover-text-accent"
                                          x-clipboard="'{{ addslashes($link->lexicon_key) }}'"
                                          aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                    <i class="bi bi-clipboard"></i>
                                </x-button>
                            </div>
                        </td>
                        <td class="p-3 font-mono text-xs">
                            <div class="flex flex-row items-center gap-xs">
                                <span>{{ $link->url }}</span>
                                <x-button type="button"
                                          variant="text"
                                          class="text-xs text-muted hover-text-accent"
                                          x-clipboard="'{{ addslashes($link->url) }}'"
                                          aria-label="{{ lexicon('tooltip_Navigation_CopyUrl', 'Copy URL to clipboard') }}"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_CopyUrl', 'Copy URL to clipboard')) }}'">
                                    <i class="bi bi-clipboard"></i>
                                </x-button>
                            </div>
                        </td>
                        <td class="p-3">
                            <x-badge variant="{{ $link->location === 'primary' ? 'success' : 'warning' }}"
                                     x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu location')) }}'">
                                {{ $link->location === 'primary' ? lexicon('label_nav_primary', 'Main Nav') : lexicon('label_nav_drawer', 'Context Drawer') }}
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
                                              @click="openLinkModal('edit', { 
                                                  id: {{ $link->id }}, 
                                                  parent_id: '',
                                                  group_name: '{{ addslashes($link->group_name) }}', 
                                                  lexicon_key: '{{ addslashes($link->lexicon_key) }}', 
                                                  url: '{{ addslashes($link->url) }}', 
                                                  location: '{{ $link->location }}', 
                                                  visibility: '{{ $link->visibility }}',
                                                  required_permission: '{{ $link->required_permission ?? '' }}', 
                                                  target: '{{ $link->target }}', 
                                                  order: {{ $link->order }}, 
                                                  group_order: {{ $link->group_order ?? 0 }} 
                                              })">
                                        <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                    </x-button>
                                    <x-button type="button"
                                              variant="text"
                                              class="text-danger hover-danger text-xs"
                                              aria-label="{{ lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item')) }}'"
                                              x-data="deleteAction(`/admin/navigation/{{ $link->id }}`, 'DELETE', '{{ addslashes($link->lexicon_key) }}', '{{ addslashes(lexicon('msg_Navigation_ConfirmDelete', 'Are you sure you want to remove this menu item?')) }}')"
                                              @click="confirmDelete()">
                                        <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                </div>
                            @endcan
                        </td>
                    </tr>

                    <!-- NESTED CHILD ROWS -->
                    @foreach($link->children as $child)
                        <tr id="link-row-{{ $child->id }}"
                            data-nav-search-item
                            data-id="{{ $child->id }}"
                            data-parent-id="{{ $link->id }}"
                            data-lexicon-key="{{ $child->lexicon_key }}"
                            data-url="{{ $child->url }}"
                            data-group-name="{{ $child->group_name ?? '' }}"
                            data-location="{{ $child->location }}"
                            x-show="isLinkVisible({{ $child->id }}, [{{$link->id }}])"
                            class="border-b-table-row hover-bg-table-row">
                            <td class="p-3 pl-6 text-muted">↳ {{ $child->order }}</td>
                            <td class="p-3 text-muted">{{ $child->group_name ?? '—' }}</td>
                            <td class="p-3 text-muted">{{ $child->group_order }}</td>
                            <td class="p-3">
                                <div class="flex flex-row items-center gap-xs">
                                    <span>{{ $child->lexicon_key }}</span>
                                    <x-button type="button"
                                              variant="text"
                                              class="text-xs text-muted hover-text-accent"
                                              x-clipboard="'{{ addslashes($child->lexicon_key) }}'"
                                              aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                        <i class="bi bi-clipboard"></i>
                                    </x-button>
                                </div>
                            </td>
                            <td class="p-3 font-mono text-xs">
                                <div class="flex flex-row items-center gap-xs">
                                    <span>{{ $child->url }}</span>
                                    <x-button type="button"
                                              variant="text"
                                              class="text-xs text-muted hover-text-accent"
                                              x-clipboard="'{{ addslashes($child->url) }}'"
                                              aria-label="{{ lexicon('tooltip_Navigation_CopyUrl', 'Copy URL to clipboard') }}"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_CopyUrl', 'Copy URL to clipboard')) }}'">
                                        <i class="bi bi-clipboard"></i>
                                    </x-button>
                                </div>
                            </td>
                            <td class="p-3">
                                <x-badge variant="{{ $child->location === 'primary' ? 'success' : 'warning' }}"
                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_LinkLocation', 'Assigned menu location')) }}'">
                                    {{ $child->location === 'primary' ? lexicon('label_nav_primary', 'Main Nav') : lexicon('label_nav_drawer', 'Context Drawer') }}
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
                                                  @click="openLinkModal('edit', { 
                                                      id: {{ $child->id }}, 
                                                      parent_id: '{{ $child->parent_id }}',
                                                      group_name: '{{ addslashes($child->group_name) }}', 
                                                      lexicon_key: '{{ addslashes($child->lexicon_key) }}', 
                                                      url: '{{ addslashes($child->url) }}', 
                                                      location: '{{ $child->location }}', 
                                                      visibility: '{{ $child->visibility }}',
                                                      required_permission: '{{ $child->required_permission ?? '' }}', 
                                                      target: '{{ $child->target }}', 
                                                      order: {{ $child->order }}, 
                                                      group_order: {{ $child->group_order ?? 0 }} 
                                                  })">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-danger hover-danger text-xs"
                                                  aria-label="{{ lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_DeleteLink', 'Delete this menu item')) }}'"
                                                  x-data="deleteAction(`/admin/navigation/{{ $child->id }}`, 'DELETE', '{{ addslashes($child->lexicon_key) }}', '{{ addslashes(lexicon('msg_Navigation_ConfirmDelete', 'Are you sure you want to remove this menu item?')) }}')"
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
            <!-- MODAL: LINK ADD / EDIT -->
            <!-- ============================================== -->

            @can('manage_navigation')
                <x-modal show="linkModal.open" maxWidth="max-w-4xl" onClose="closeLinkModal()">
                    <x-slot:top>
                        <span x-text="linkModal.mode === 'create' ? '{{ addslashes(lexicon('label_nav_CreateLink', 'Add Menu Item')) }}' : '{{ addslashes(lexicon('label_nav_EditLink', 'Edit Menu Item')) }}'"></span>
                    </x-slot:top>

                    <form :action="linkModal.formAction"
                          method="POST"
                          class="flex flex-col gap-md"
                          x-data="ajaxForm"
                          @submit.prevent="submit"
                          data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                        @csrf
                        <input type="hidden" name="_method" :value="linkModal.method">

                        <div x-ref="navModalDropdowns" class="flex flex-col gap-md">
                            <div class="grid grid-cols-1 gap-sm w-full lg-grid-cols-3 bg-panel border border-panel rounded-panel shadow-panel p-3">
                                <div class="flex flex-col gap-xs">
                                    <label for="modal_parent_id" class="text-xs font-bold text-input-muted">{{ lexicon('label_nav_ParentLink', 'Parent Link (Optional Dropdown)') }}</label>
                                    <select id="modal_parent_id"
                                            name="parent_id"
                                            x-model="linkModal.parent_id"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_nav_ParentLink', 'Parent Link (Optional Dropdown)') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_ParentLink', 'Assign under a top-level main nav link to create a dropdown item')) }}'">
                                        <option value="">— {{ lexicon('label_common_None', 'Top Level') }} —</option>
                                        @foreach($parentOptions as $parent)
                                            <option value="{{ $parent->id }}">{{ lexicon($parent->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$parent->lexicon_key))) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label for="modal_group_name" class="text-xs font-bold text-input-muted">{{ lexicon('label_nav_group', 'Group Name') }}</label>
                                    <select id="modal_group_name"
                                            name="group_name"
                                            x-model="linkModal.group_name"
                                            @change="
                                                let selected = $el.options[$el.selectedIndex];
                                                if (selected && selected.dataset.order) {
                                                    linkModal.group_order = selected.dataset.order;
                                                }
                                            "
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_nav_group', 'Group Name') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_GroupName', 'Assign this link to a collapsible drawer group')) }}'">
                                        <option value="">— {{ lexicon('label_common_None', 'None') }} —</option>
                                        @foreach($groupOptions as $opt)
                                            <option value="{{ $opt->value }}" data-order="{{ $opt->sort_order }}">{{ lexicon($opt->label,$opt->value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <x-form-input name="group_order"
                                              id="modal_group_order"
                                              type="number"
                                              :label="lexicon('label_common_GroupSortOrder', 'Group Sort Order')"
                                              :tooltip="lexicon('tooltip_Navigation_GroupSortOrder', 'Numeric display order for the group')"
                                              xModel="linkModal.group_order" />

                                <x-form-input name="lexicon_key"
                                              id="modal_lexicon_key"
                                              type="text"
                                              :label="lexicon('label_nav_LexiconKey', 'Lexicon Key')"
                                              :tooltip="lexicon('tooltip_Navigation_LexiconKey', 'Translation code key for this menu item')"
                                              xModel="linkModal.lexicon_key"
                                              :required="true" />

                                <x-form-input name="url"
                                              id="modal_url"
                                              type="text"
                                              :label="lexicon('label_nav_url', 'URL Address')"
                                              :tooltip="lexicon('tooltip_Navigation_Url', 'Destination path or full URL')"
                                              xModel="linkModal.url"
                                              :required="true" />

                                <x-form-input name="order"
                                              id="modal_order"
                                              type="number"
                                              :label="lexicon('label_sort_order', 'Sort Order')"
                                              :tooltip="lexicon('tooltip_Navigation_SortOrder', 'Numeric display order within its group or bar')"
                                              xModel="linkModal.order"
                                              :required="true" />
                            </div>

                            <div class="grid grid-cols-1 gap-sm w-full lg-grid-cols-3 bg-panel border border-panel rounded-panel shadow-panel p-3">
                                <div class="flex flex-col gap-xs">
                                    <label for="modal_location" class="text-xs font-bold text-input-muted">{{ lexicon('label_nav_location', 'Location') }}</label>
                                    <select id="modal_location"
                                            name="location"
                                            x-model="linkModal.location"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_nav_location', 'Location') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_Location', 'Display in the Main Nav bar or Context Drawer')) }}'">
                                        @foreach($locationOptions as $opt)
                                            <option value="{{ $opt->value }}">{{ lexicon($opt->label,$opt->value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label for="modal_visibility" class="text-xs font-bold text-input-muted">{{ lexicon('label_vis_title', 'Visibility') }}</label>
                                    <select id="modal_visibility"
                                            name="visibility"
                                            x-model="linkModal.visibility"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            aria-label="{{ lexicon('label_vis_title', 'Visibility') }}"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_Visibility', 'Minimum user access level required to view this link')) }}'">
                                        @foreach($visibilityOptions as $opt)
                                            <option value="{{ $opt->value }}">{{ lexicon($opt->label,$opt->value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs">
                                    <label for="modal_target" class="text-xs font-bold text-input-muted">{{ lexicon('label_target_WindowTarget', 'Window Target') }}</label>
                                    <select id="modal_target"
                                            name="target"
                                            x-model="linkModal.target"
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
                                        x-model="linkModal.required_permission"
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                        aria-label="{{ lexicon('label_PageBuilder_RequiredPermission', 'Required Permission') }}"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_RequiredPermission', 'Restrict this link to users holding a specific permission')) }}'">
                                    <option value="">— {{ lexicon('label_Roles_NoneVisibilityBased', 'None (Public / Visibility Based)') }} —</option>
                                    @foreach($permissions as $perm)
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
                                <span x-text="linkModal.mode === 'create' ? '{{ addslashes(lexicon('btn_nav_SaveLink', 'Save Menu Item')) }}' : '{{ addslashes(lexicon('btn_nav_UpdateLink', 'Update Menu Item')) }}'"></span>
                            </x-button>
                        </div>
                    </form>
                </x-modal>
            @endcan
        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection