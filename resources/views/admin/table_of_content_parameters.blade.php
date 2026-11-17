{{-- resources/views/admin/table_of_content_parameters.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_NavOptions', 'Table of Contents Parameters'))

@section('content')
<div x-data="tableOfContentParametersManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="tocParametersTableWrapper"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_NavOptions', 'Table of Contents Parameters')"
                           :subtitle="lexicon('desc_admin_NavOptions', 'Configure custom navigation groups, display locations, and access clearance options for the Table of Contents.')"
                           icon="bi-sliders2">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="param_search"
                                id="param_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('label_Navigation_FilterOptionsPlaceholder', 'Search options by type, value, or token...')"
                                :tooltip="lexicon('tooltip_Navigation_FilterOptionsInput', 'Instantaneously filter dropdown options using fuzzy search')" />

                    @can('manage_navigation')
                        <x-button variant="secondary"
                                  size="sm"
                                  href="{{ route('admin.table_of_contents.index') }}"
                                  icon="bi-list-nested"
                                  :tooltip="lexicon('tooltip_Navigation_BackToManager', 'Return to Table of Contents Manager')">
                            {{ lexicon('btn_Navigation_Manager', 'Menu Manager') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_Navigation_AddNewOption', 'Create a new Table of Contents parameter')"
                                  @click="openModal('create')">
                            + {{ lexicon('label_Navigation_AddNewOption', 'Add Parameter') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Registered Parameters Table --}}
            <x-tab type="accordion"
                   :title="lexicon('label_Navigation_RegisteredOptions', 'Registered Table of Contents Parameters')"
                   icon="bi-table"
                   :open="true">
                <x-table type="admin"
                         :headers="[
                             lexicon('label_Navigation_OptionType', 'Parameter Mode'),
                             lexicon('label_Navigation_OptionValueShort', 'Database Value'),
                             lexicon('label_Navigation_OptionLabelShort', 'Lexicon Token'),
                             lexicon('label_common_SortOrder', 'Sort Order'),
                             lexicon('label_common_Actions', 'Actions')
                         ]"
                         :emptyText="lexicon('msg_Navigation_NoOptionsFound', 'No Table of Contents parameters registered.')">
                    @foreach($parameters ?? [] as $parameter)
                        @php
                            $modeVariant = match($parameter->parameter_mode) {
                                'group'            => 'primary',
                                'display_location' => 'success',
                                default            => 'warning',
                            };
                        @endphp
                        <tr data-search-item
                            data-toc-parameter-item
                            data-id="{{ $parameter->id }}"
                            data-parameter_mode="{{ $parameter->parameter_mode }}"
                            data-parameter_value="{{ $parameter->parameter_value }}"
                            data-parameter_label="{{ $parameter->parameter_label ?? '' }}"
                            x-show="isItemVisible({{ $parameter->id }})"
                            class="border-b-table-row hover-bg-panel-hover transition-colors">
                            <td class="p-4">
                                <x-badge type="pill"
                                         :variant="$modeVariant"
                                         :tooltip="lexicon('tooltip_Navigation_OptionCategoryBadge', 'Parameter category mode')">
                                    {{ ucfirst(str_replace('_', ' ', $parameter->parameter_mode)) }}
                                </x-badge>
                            </td>
                            <td class="p-4 font-bold text-h">{{ $parameter->parameter_value }}</td>
                            <td class="p-4 font-mono text-xs text-accent">
                                @if($parameter->parameter_label)
                                    <div class="flex flex-row items-center gap-xs">
                                        <span>{{ $parameter->parameter_label }}</span>
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-clipboard"
                                                  :tooltip="lexicon('tooltip_common_CopyToken', 'Copy token name to clipboard')"
                                                  x-clipboard="'{{ addslashes($parameter->parameter_label) }}'" />
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-4 font-mono text-xs text-p">{{ $parameter->parameter_sort_order }}</td>
                            <td class="p-4 text-right">
                                @can('manage_navigation')
                                    <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-pencil"
                                                  :tooltip="lexicon('tooltip_Navigation_EditOption', 'Edit this parameter')"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'id' => $parameter->id,
                                                      'parameterId' => $parameter->id,
                                                      'parameter_mode' => $parameter->parameter_mode,
                                                      'parameter_value' => $parameter->parameter_value,
                                                      'parameter_label' => $parameter->parameter_label ?? '',
                                                      'parameter_sort_order' => $parameter->parameter_sort_order ?? 0,
                                                  ]) }})">
                                            {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  :tooltip="lexicon('tooltip_Navigation_DeleteOption', 'Delete this parameter')"
                                                  x-data="deleteAction(
                                                      '/admin/table-of-contents/options/{{ $parameter->id }}',
                                                      'DELETE',
                                                      '{{ addslashes($parameter->parameter_value) }}',
                                                      '{{ addslashes(lexicon('msg_Navigation_ConfirmDeleteOption', 'Are you sure you want to delete this dropdown option?')) }}'
                                                  )"
                                                  @click="confirmDelete()">
                                            {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-tab>

            {{-- MODAL: CREATE / EDIT PARAMETER --}}
            @can('manage_navigation')
                <x-modal show="modals.parameter.open" onClose="closeModal()" maxWidth="max-w-lg">
                    <x-slot:top>
                        <span x-text="modals.parameter.mode === 'edit'
                            ? '{{ addslashes(lexicon('label_Navigation_EditingOption', 'Edit Parameter')) }}: ' + (modals.parameter.parameter_value || '')
                            : '{{ addslashes(lexicon('label_Navigation_AddNewOption', 'Create Table of Contents Parameter')) }}'"></span>
                    </x-slot:top>

                    <x-meta type="modal" xAction="modals.parameter.action" xMethod="modals.parameter.method">
                        <x-metadata type="select"
                                    name="parameter_mode"
                                    id="parameter_mode"
                                    xModel="modals.parameter.parameter_mode"
                                    :label="lexicon('label_Navigation_OptionType', 'Parameter Mode')"
                                    :tooltip="lexicon('tooltip_Navigation_OptionTypeSelect', 'Select the category of dropdown option')"
                                    :required="true">
                            <option value="group">{{ lexicon('label_common_group', 'Group') }}</option>
                            <option value="display_location">{{ lexicon('label_nav_location', 'Display Location') }}</option>
                            <option value="access_clearance">{{ lexicon('label_vis_title', 'Access Clearance') }}</option>
                        </x-metadata>

                        <x-metadata type="text"
                                    name="parameter_value"
                                    id="parameter_value"
                                    xModel="modals.parameter.parameter_value"
                                    :label="lexicon('label_Navigation_OptionValue', 'Value (Database Key)')"
                                    :placeholder="lexicon('label_Navigation_OptionValuePlaceholder', 'e.g. primary, Lore')"
                                    :tooltip="lexicon('tooltip_Navigation_OptionValueInput', 'Internal database key stored on navigation links')"
                                    :required="true" />

                        <x-metadata type="text"
                                    name="parameter_label"
                                    id="parameter_label"
                                    xModel="modals.parameter.parameter_label"
                                    :label="lexicon('label_Navigation_OptionLabelKey', 'Label (Lexicon Token)')"
                                    :placeholder="lexicon('label_Navigation_OptionLabelPlaceholder', 'e.g. label_nav_group_lore')"
                                    :tooltip="lexicon('tooltip_Navigation_OptionLabelInput', 'Lexicon code key used to translate this option')" />

                        <x-metadata type="number"
                                    name="parameter_sort_order"
                                    id="parameter_sort_order"
                                    xModel="modals.parameter.parameter_sort_order"
                                    :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                    :tooltip="lexicon('tooltip_Navigation_OptionSortOrder', 'Numeric display order in dropdown menus')" />

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
                                      :tooltip="lexicon('tooltip_Navigation_SaveOption', 'Save navigation dropdown option')">
                                <span x-text="modals.parameter.mode === 'create'
                                    ? '{{ addslashes(lexicon('btn_Navigation_SaveOption', 'Save Parameter')) }}'
                                    : '{{ addslashes(lexicon('btn_Navigation_UpdateOption', 'Update Parameter')) }}'"></span>
                            </x-button>
                        </x-slot:footer>
                    </x-meta>
                </x-modal>
            @endcan
        </x-slot:content>
    </x-grid-wrapper>
</div>
@endsection