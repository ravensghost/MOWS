@extends('layouts.app')
@section('title', lexicon('admin_label_NavOptions', 'Navigation Options'))

@section('content')
<div x-data="navOptionsManager"
     @ajax-success.window="refreshDOM()"
     data-msg-success-title="{{ lexicon('msg_common_Success', 'Success!') }}"
     data-msg-success-text="{{ lexicon('msg_Navigation_OptionSaved', 'Option saved successfully.') }}"
     data-msg-error-title="{{ lexicon('msg_common_Error', 'Error') }}"
     data-msg-validation-failed="{{ lexicon('msg_common_ValidationFailed', 'Validation failed.') }}"
     data-msg-network-error-title="{{ lexicon('msg_common_NetworkError', 'Network Error') }}"
     data-msg-network-error-text="{{ lexicon('msg_common_NetworkErrorDesc', 'Could not connect to the server.') }}">
    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <!-- Page Header -->
            <x-page-header :title="lexicon('admin_label_NavOptions', 'Navigation Options')">
                <x-slot:actions>
                    <x-button variant="success"
                              href="/admin/navigation"
                              aria-label="{{ lexicon('tooltip_Navigation_BackToNav', 'Return to Navigation Manager') }}"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_BackToNav', 'Return to Navigation Manager')) }}'">
                        &larr; {{ lexicon('btn_Navigation_BackToNav', 'Back to Navigation Manager') }}
                    </x-button>
                </x-slot:actions>
            </x-page-header>

            <!-- Form Panel -->
            @can('manage_navigation')
                <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col gap-md overflow-hidden p-4 mb-lg">
                    <div class="mb-3 border-b pb-2 flex flex-row flex-wrap gap-md items-center justify-between">
                        <span class="text-sm font-bold text-accent"
                              x-text="formMode === 'create' ? '{{ addslashes(lexicon('label_Navigation_AddNewOption', '+ Add New Option')) }}' : '{{ addslashes(lexicon('label_Navigation_EditingOption', 'Editing Option:')) }} ' + optValue"></span>
                        <x-button type="button"
                                  variant="text"
                                  class="text-xs text-muted hover-text-h"
                                  x-show="formMode === 'edit'"
                                  @click="resetForm()"
                                  aria-label="{{ lexicon('tooltip_Navigation_CancelEdit', 'Cancel editing option') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_CancelEdit', 'Cancel editing option')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                    </div>

                    <form @submit.prevent="submitOption()" class="flex flex-col gap-md">
                        <div class="grid grid-cols-1 gap-sm w-full lg-grid-cols-4">
                            <div class="flex flex-col gap-xs">
                                <label for="opt_type" class="text-xs font-bold text-input-muted">
                                    <span>{{ lexicon('label_Navigation_OptionType', 'Type') }}</span>
                                    <span class="text-required">*</span>
                                </label>
                                <select id="opt_type"
                                        x-model="optType"
                                        class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                        aria-label="{{ lexicon('label_Navigation_OptionType', 'Type') }}"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_OptionTypeSelect', 'Select the category of dropdown option')) }}'"
                                        required>
                                    <option value="group">{{ lexicon('label_common_group', 'Group') }}</option>
                                    <option value="location">{{ lexicon('label_nav_location', 'Location') }}</option>
                                    <option value="visibility">{{ lexicon('label_vis_title', 'Visibility') }}</option>
                                </select>
                            </div>

                            <x-form-input name="opt_value"
                                          id="opt_value"
                                          type="text"
                                          :label="lexicon('label_Navigation_OptionValue', 'Value (Database Key)')"
                                          :placeholder="lexicon('label_Navigation_OptionValuePlaceholder', 'e.g. primary, Lore')"
                                          :tooltip="lexicon('tooltip_Navigation_OptionValueInput', 'Internal database key stored on navigation links')"
                                          xModel="optValue"
                                          :required="true" />

                            <x-form-input name="opt_label"
                                          id="opt_label"
                                          type="text"
                                          :label="lexicon('label_Navigation_OptionLabelKey', 'Label (Lexicon Key)')"
                                          :placeholder="lexicon('label_Navigation_OptionLabelPlaceholder', 'e.g. label_nav_group_lore')"
                                          :tooltip="lexicon('tooltip_Navigation_OptionLabelInput', 'Lexicon code key used to translate this option')"
                                          xModel="optLabel" />

                            <x-form-input name="opt_sort_order"
                                          id="opt_sort_order"
                                          type="number"
                                          :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                          :tooltip="lexicon('tooltip_Navigation_OptionSortOrder', 'Numeric display order in dropdown menus')"
                                          xModel="optSortOrder" />
                        </div>

                        <div class="flex flex-row flex-wrap items-center justify-end pt-2">
                            <x-button type="submit"
                                      variant="primary"
                                      class="px-6 py-2"
                                      x-bind:disabled="isSubmitting"
                                      aria-label="{{ lexicon('tooltip_Navigation_SaveOption', 'Save navigation dropdown option') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_SaveOption', 'Save navigation dropdown option')) }}'">
                                <span x-text="formMode === 'create' ? '{{ addslashes(lexicon('btn_Navigation_SaveOption', 'Save Option')) }}' : '{{ addslashes(lexicon('btn_Navigation_UpdateOption', 'Update Option')) }}'"></span>
                            </x-button>
                        </div>
                    </form>
                </div>
            @endcan

            <!-- AJAX Swappable Options Table -->
            <x-accordion-panel :title="lexicon('label_Navigation_RegisteredOptions', 'Registered Dropdown Options')" :expanded="true">
                <div class="mb-2">
                    <x-form-input name="opt_search"
                                  id="opt_search"
                                  :label="lexicon('label_Navigation_FilterOptions', 'Filter Dropdown Options')"
                                  :placeholder="lexicon('label_Navigation_FilterOptionsPlaceholder', 'Search options by type, value, or lexicon key...')"
                                  :tooltip="lexicon('tooltip_Navigation_FilterOptionsInput', 'Instantaneously filter dropdown options using fuzzy search')"
                                  xModel="searchQuery" />
                </div>

                <x-table x-ref="optionsTableContent" class="border-t-0">
                    <x-slot:headers>
                        <th class="p-3">{{ lexicon('label_Navigation_OptionType', 'Type') }}</th>
                        <th class="p-3">{{ lexicon('label_Navigation_OptionValueShort', 'Value') }}</th>
                        <th class="p-3">{{ lexicon('label_Navigation_OptionLabelShort', 'Label Key') }}</th>
                        <th class="p-3">{{ lexicon('label_common_SortOrder', 'Sort Order') }}</th>
                        <th class="p-3 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                    </x-slot:headers>

                    @forelse($options as $option)
                        <tr data-opt-search-item
                            data-id="{{ $option->id }}"
                            data-type="{{ $option->option_type }}"
                            data-value="{{ $option->value }}"
                            data-label="{{ $option->label ?? '' }}"
                            x-show="isOptionVisible({{ $option->id }})"
                            class="border-b-table-row hover-bg-table-row">
                            <td class="p-3">
                                <x-badge variant="{{ $option->option_type === 'group' ? 'primary' : ($option->option_type === 'location' ? 'success' : 'warning') }}"
                                         x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_OptionCategoryBadge', 'Option category type')) }}'">
                                    {{ ucfirst($option->option_type) }}
                                </x-badge>
                            </td>
                            <td class="p-3 font-bold">{{ $option->value }}</td>
                            <td class="p-3 text-muted text-xs font-mono">
                                @if($option->label)
                                    <div class="flex flex-row items-center gap-xs">
                                        <span>{{ $option->label }}</span>
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-xs text-muted hover-text-accent"
                                                  x-clipboard="'{{ addslashes($option->label) }}'"
                                                  aria-label="{{ lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')) }}'">
                                            <i class="bi bi-clipboard"></i>
                                        </x-button>
                                    </div>
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="p-3">{{ $option->sort_order }}</td>
                            <td class="p-3 text-right">
                                @can('manage_navigation')
                                    <div class="flex flex-row flex-wrap gap-sm items-center justify-end">
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-warning hover-warning text-xs"
                                                  @click="editOption({{ json_encode($option) }})"
                                                  aria-label="{{ lexicon('tooltip_Navigation_EditOption', 'Edit this dropdown option') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_EditOption', 'Edit this dropdown option')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>
                                        <x-button type="button"
                                                  variant="text"
                                                  class="text-danger hover-danger text-xs"
                                                  x-data="deleteAction(
                                                      '/admin/navigation/options/{{ $option->id }}',
                                                      'DELETE',
                                                      '{{ addslashes($option->value) }}',
                                                      '{{ addslashes(lexicon('msg_Navigation_ConfirmDeleteOption', 'Are you sure you want to delete this dropdown option?')) }}'
                                                  )"
                                                  @click="confirmDelete()"
                                                  aria-label="{{ lexicon('tooltip_Navigation_DeleteOption', 'Delete this dropdown option') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Navigation_DeleteOption', 'Delete this dropdown option')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-muted italic">{{ lexicon('msg_Navigation_NoOptionsFound', 'No navigation options registered.') }}</td>
                        </tr>
                    @endforelse
                </x-table>
            </x-accordion-panel>
        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection