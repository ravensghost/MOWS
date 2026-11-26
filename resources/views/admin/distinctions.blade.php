{{-- resources/views/admin/distinctions.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_Awards', 'Distinction & Award Management'))

@section('content')
<x-grid-wrapper :book="$systemBook">
    <x-slot:content>
        <div x-data="distinctionManager()"
             @ajax-success.window="refreshDOM()"
             x-ref="distinctionsTableWrapper"
             class="flex flex-col gap-lg">

            {{-- Page Header --}}
            <x-page-header :title="lexicon('admin_label_Awards', 'Distinction & Award Management')"
                           :subtitle="lexicon('desc_admin_Awards', 'Configure 3-layered IC/OOC distinction medals, ribbons, and subgroup catalogues.')"
                           icon="bi-award">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="distinction_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_awards_Search', 'Search distinctions...')"
                                :tooltip="lexicon('tooltip_awards_Search', 'Filter distinctions by title or synopsis')" />

                    @can('manage_awards')
                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-folder2-open"
                                  :tooltip="lexicon('tooltip_awards_ManageSubgroups', 'Manage distinction subgroup categories')"
                                  @click="openModal('create', {}, 'catalogue')">
                            {{ lexicon('btn_awards_Subgroups', 'Manage Subgroups') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_awards_CreateAward', 'Create a new distinction badge')"
                                  @click="openModal('create', {}, 'distinction')">
                            + {{ lexicon('btn_awards_CreateAward', 'Create Distinction') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Distinctions Table --}}
            <x-tab type="accordion"
                   :title="lexicon('heading_awards_Catalogue', 'Configured Distinctions')"
                   icon="bi-trophy"
                   :open="true">
                <x-table type="admin"
                         :headers="[
                             lexicon('label_common_Name', 'Distinction Title'),
                             lexicon('label_awards_Grouping', 'Scope & Catalogue'),
                             lexicon('label_common_Type', 'Layer Mode'),
                             lexicon('label_common_Preview', 'Preview'),
                             lexicon('label_common_Actions', 'Actions')
                         ]"
                         :emptyText="lexicon('msg_no_awards', 'No distinctions configured yet.')">
                    @foreach($distinctions ?? [] as $distinction)
                        @php
                            $scopeVariant = match($distinction->scope_target) {
                                'IC'    => 'primary',
                                'OOC'   => 'warning',
                                default => 'success',
                            };
                            $distinctionSynopsis = $distinction->distinction_synopsis ?? $distinction->distinction_description ?? '';
                        @endphp
                        <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                            data-search-item
                            data-distinction-item
                            data-id="{{ $distinction->id }}"
                            data-distinction_title="{{ $distinction->distinction_title }}"
                            data-distinction_synopsis="{{ $distinctionSynopsis }}"
                            x-show="isItemVisible({{ $distinction->id }})">
                            <td class="p-4">
                                <span class="font-bold text-h block">{{ $distinction->distinction_title }}</span>
                                @if($distinctionSynopsis !== '')
                                    <span class="text-xs text-muted block">{{ Str::limit($distinctionSynopsis, 80) }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex flex-row flex-wrap items-center gap-xs">
                                    <x-badge type="pill" :variant="$scopeVariant">
                                        {{ $distinction->scope_target }}
                                    </x-badge>
                                    @if($distinction->catalogue)
                                        <span class="text-xs text-muted">/ {{ $distinction->catalogue->catalogue_title }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4">
                                <x-badge type="pill" :variant="!empty($distinction->top_layer_mode) && $distinction->top_layer_mode !== 'none' ? 'info' : 'muted'">
                                    {{ !empty($distinction->top_layer_mode) && $distinction->top_layer_mode !== 'none'
                                        ? lexicon('label_awards_3Layered', '3-Layered')
                                        : lexicon('label_awards_BaseMidOnly', 'Base / Mid') }}
                                </x-badge>
                            </td>
                            <td class="p-4">
                                <x-badge type="distinction"
                                         :award="$distinction"
                                         :skin="$activeSkin->directory_name ?? 'default'"
                                         :tooltip="$distinction->distinction_title" />
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                    <x-button type="button"
                                              variant="ghost"
                                              size="sm"
                                              icon="bi-pencil"
                                              :tooltip="lexicon('tooltip_common_EditAward', 'Edit this distinction configuration')"
                                              @click="openModal('edit', {{ Js::from([
                                                  'id' => $distinction->id,
                                                  'distinction_title' => $distinction->distinction_title,
                                                  'distinction_synopsis' => $distinctionSynopsis,
                                                  'scope_target' => $distinction->scope_target,
                                                  'distinction_catalogue_id' => $distinction->distinction_catalogue_id ?? '',
                                                  'distinction_base_colour' => $distinction->distinction_base_colour ?? '#1a1a1aFF',
                                                  'mid_layer_mode' => $distinction->mid_layer_mode ?? 'css',
                                                  'mid_layer_asset' => $distinction->mid_layer_asset ?? '',
                                                  'mid_layer_modifier' => $distinction->mid_layer_modifier ?? 'original',
                                                  'mid_layer_hue' => $distinction->mid_layer_hue ?? '#1a1a1aFF',
                                                  'top_layer_mode' => $distinction->top_layer_mode ?? 'image',
                                                  'top_layer_asset' => $distinction->top_layer_asset ?? '',
                                                  'top_layer_modifier' => $distinction->top_layer_modifier ?? '',
                                                  'top_layer_hue' => $distinction->top_layer_hue ?? '#ffffffFF',
                                              ]) }}, 'distinction')">
                                        {{ lexicon('btn_common_Edit', 'Edit') }}
                                    </x-button>

                                    <x-button type="button"
                                              variant="danger"
                                              size="sm"
                                              icon="bi-trash"
                                              :tooltip="lexicon('tooltip_common_DeleteAward', 'Delete this distinction permanently')"
                                              x-data="deleteAction('{{ route('admin.distinctions.destroy', $distinction) }}', 'DELETE', '{{ addslashes($distinction->distinction_title) }}')"
                                              @click="confirmDelete()">
                                        {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-tab>

            {{-- MODAL 1: CREATE / EDIT DISTINCTION --}}
            <x-modal show="modals.distinction.open" onClose="closeModal('distinction')" maxWidth="4xl">
                <x-slot:top>
                    <span x-text="modals.distinction.mode === 'edit'
                        ? '{{ addslashes(lexicon('label_edit_award', 'Edit Distinction')) }}: ' + (modals.distinction.distinction_title || '')
                        : '{{ addslashes(lexicon('label_create_award', 'Create Distinction')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.distinction.action" xMethod="modals.distinction.method">
                    <div class="grid grid-cols-1 lg-grid-cols-12 gap-md items-start">
                        <div class="lg-col-span-8 flex flex-col gap-md">
                            <x-metadata type="text"
                                        name="distinction_title"
                                        xModel="modals.distinction.distinction_title"
                                        :label="lexicon('label_awards_AwardName', 'Distinction Title')"
                                        :tooltip="lexicon('tooltip_awards_NameInput', 'Enter the full title of the distinction')"
                                        :required="true" />

                            <x-metadata type="textarea"
                                        name="distinction_synopsis"
                                        xModel="modals.distinction.distinction_synopsis"
                                        :label="lexicon('label_common_Synopsis', 'Citation / Synopsis')"
                                        :tooltip="lexicon('tooltip_awards_DescriptionInput', 'Provide a brief explanation or citation for this distinction')" />

                            <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                                <x-metadata type="context-select"
                                            name="scope_target"
                                            :detailedLabels="true"
                                            xModel="modals.distinction.scope_target"
                                            :label="lexicon('label_awards_MasterGroup', 'Target Scope (IC / OOC / BOTH)')"
                                            :tooltip="lexicon('tooltip_awards_MasterGroupSelect', 'Select whether this distinction targets users, personae, or both')"
                                            :required="true" />

                                <x-metadata type="select"
                                            name="distinction_catalogue_id"
                                            xModel="modals.distinction.distinction_catalogue_id"
                                            :label="lexicon('label_awards_SubgroupCategory', 'Subgroup Catalogue')"
                                            :tooltip="lexicon('tooltip_awards_SubgroupSelect', 'Assign this distinction to a subgroup category')">
                                    <option value="">{{ lexicon('label_awards_NoSubgroup', '— No Subgroup —') }}</option>
                                    @foreach($catalogues ?? [] as $catalogue)
                                        <option value="{{ $catalogue->id }}">{{ $catalogue->catalogue_title }}</option>
                                    @endforeach
                                </x-metadata>
                            </div>

                            <div class="border-t-panel pt-3">
                                <x-colour-picker name="distinction_base_colour"
                                                 xModel="modals.distinction.distinction_base_colour"
                                                 :label="lexicon('label_awards_BaseColor', 'Base Layer Colour (8-Digit Hex #RRGGBBAA)')"
                                                 :tooltip="lexicon('tooltip_awards_BaseColor', 'Select the base background colour and alpha for this distinction')" />
                            </div>

                            {{-- Reusable Polymorphic 3-Layer Component bound to modals.distinction --}}
                            <div class="border-t-panel pt-3">
                                <x-metadata type="layer"
                                            modalKey="distinction"
                                            :midShapes="$midShapes ?? []"
                                            :topShapes="$topShapes ?? []" />
                            </div>
                        </div>

                        <div class="lg-col-span-4 flex flex-col gap-md">
                            <x-preview type="distinction" modalKey="distinction">
                                <x-badge type="distinction"
                                         :skin="$activeSkin->directory_name ?? 'default'"
                                         :reactive="true" />
                            </x-preview>
                        </div>
                    </div>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_CancelModal', 'Cancel and close modal')"
                                  @click="closeModal('distinction')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_common_SaveAward', 'Save distinction configuration')">
                            {{ lexicon('btn_common_Save', 'Save Distinction') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: SUBGROUP CATALOGUE MANAGER --}}
            <x-modal show="modals.catalogue.open" onClose="closeModal('catalogue')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_awards_ManageSubgroups', 'Manage Distinction Subgroups') }}</span>
                </x-slot:top>

                <div class="flex flex-col gap-md">
                    <x-meta type="default" :action="route('admin.distinctions.catalogues.store')" method="POST">
                        <div class="flex flex-row items-end gap-sm">
                            <div class="flex-grow">
                                <x-metadata type="text"
                                            name="catalogue_title"
                                            :label="lexicon('label_awards_NewSubgroup', 'New Subgroup Title')"
                                            :placeholder="lexicon('msg_awards_NewSubgroupName', 'e.g. Campaign Ribbons')"
                                            :tooltip="lexicon('tooltip_awards_NewSubgroupInput', 'Enter new distinction category title')"
                                            :required="true" />
                            </div>
                            <x-button type="submit"
                                      variant="success"
                                      icon="bi-plus-lg"
                                      :tooltip="lexicon('tooltip_common_AddCategory', 'Add new category subgroup')">
                                {{ lexicon('btn_common_Add', 'Add') }}
                            </x-button>
                        </div>
                    </x-meta>

                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_awards_SubgroupName', 'Subgroup Title'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_awards_NoSubgroups', 'No subgroups defined.')">
                        @foreach($catalogues ?? [] as $catalogue)
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                <td class="p-3">
                                    <x-meta type="default" :action="route('admin.distinctions.catalogues.update', $catalogue)" method="PUT">
                                        <div class="flex flex-row items-center gap-xs">
                                            <x-metadata type="text"
                                                        name="catalogue_title"
                                                        :value="$catalogue->catalogue_title"
                                                        :tooltip="lexicon('tooltip_awards_EditSubgroupInput', 'Edit category title')"
                                                        :required="true" />
                                            <x-button type="submit"
                                                      variant="ghost"
                                                      size="sm"
                                                      icon="bi-check2"
                                                      :tooltip="lexicon('tooltip_common_SaveEdit', 'Save subgroup title')" />
                                        </div>
                                    </x-meta>
                                </td>
                                <td class="p-3 text-right">
                                    <x-button type="button"
                                              variant="danger"
                                              size="sm"
                                              icon="bi-trash"
                                              :tooltip="lexicon('tooltip_common_DeleteSubgroup', 'Delete this subgroup')"
                                              x-data="deleteAction('{{ route('admin.distinctions.catalogues.destroy', $catalogue) }}', 'DELETE', '{{ addslashes($catalogue->catalogue_title) }}')"
                                              @click="confirmDelete()">
                                        {{ lexicon('btn_common_Delete', 'Delete') }}
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>

                <x-slot:bottom>
                    <x-button type="button"
                              variant="secondary"
                              @click="closeModal('catalogue')">
                        {{ lexicon('btn_common_Close', 'Close') }}
                    </x-button>
                </x-slot:bottom>
            </x-modal>

        </div>
    </x-slot:content>
</x-grid-wrapper>
@endsection