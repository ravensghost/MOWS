{{-- resources/views/admin/meta.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_FormsHub', 'Metadata & Forms Hub'))

@section('content')
<div x-data="metadataManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="metadataTableContent"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_FormsHub', 'Metadata & Forms Hub')"
                           :subtitle="lexicon('desc_admin_FormsHub', 'Configure candidate petition forms, persona metadata fields, and chronological service record timeline inputs.')"
                           icon="bi-ui-checks-grid">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="metadata_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_FormsHub_Search', 'Search forms or fields...')"
                                :tooltip="lexicon('tooltip_FormsHub_Search', 'Filter metadata forms and fields by title or mode')" />

                    @can('manage_forms')
                        <x-button variant="secondary"
                                  size="sm"
                                  href="{{ route('admin.books.index') }}"
                                  icon="bi-journal-richtext"
                                  :tooltip="lexicon('tooltip_FormsHub_ManagePages', 'Manage portal books and grid layouts')">
                            {{ lexicon('btn_FormsHub_ManagePages', 'Manage Books') }}
                        </x-button>

                        <x-button type="button"
                                  variant="secondary"
                                  size="sm"
                                  icon="bi-input-cursor-text"
                                  :tooltip="lexicon('tooltip_FormsHub_AddField', 'Add a new metadata input field')"
                                  @click="openField('create')">
                            + {{ lexicon('btn_FormsHub_AddFormField', 'Add Field') }}
                        </x-button>

                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-plus-lg"
                                  :tooltip="lexicon('tooltip_FormsHub_CreateForm', 'Create a new metadata form')"
                                  @click="openForm('create')">
                            + {{ lexicon('btn_FormsHub_CreateNewForm', 'New Form') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:bottom>
            <div class="flex flex-col gap-lg">
                {{-- 1. ALL FORMS TABLE --}}
                <x-tab type="accordion"
                       :title="lexicon('label_FormsHub_AllForms', 'All Metadata Forms (Joining, Petition, Profile)')"
                       icon="bi-card-checklist"
                       :open="true">
                    <div id="forms-table-content" x-ref="formsTableContent">
                        <x-table type="admin"
                                 :headers="[
                                     lexicon('label_common_FormName', 'Form Title'),
                                     lexicon('label_FormsHub_AttachedFields', 'Attached Fields'),
                                     lexicon('label_common_Status', 'Status'),
                                     lexicon('label_common_Actions', 'Actions')
                                 ]"
                                 :emptyText="lexicon('msg_FormsHub_NoForms', 'No custom metadata forms have been established yet.')">
                            @foreach($forms ?? [] as $form)
                                @php
                                    $attachedFields = ($fields ?? collect())->where('linked_metadata', $form->id);
                                @endphp
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                    data-search-item
                                    data-metadata-item
                                    data-id="{{ $form->id }}"
                                    data-metadata_title="{{ $form->metadata_title }}"
                                    x-show="isItemVisible({{ $form->id }})">
                                    <td class="p-4 font-bold text-h">{{ $form->metadata_title }}</td>
                                    <td class="p-4 text-sm text-muted">
                                        @if($attachedFields->count() > 0)
                                            {{ $attachedFields->pluck('metadata_title')->join(', ') }}
                                        @else
                                            <span class="font-italic">{{ lexicon('msg_FormsHub_NoFieldsAssigned', 'No fields assigned') }}</span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <x-badge type="pill" :variant="$form->is_visible ? 'success' : 'danger'">
                                            {{ $form->is_visible ? lexicon('label_common_Published', 'Published') : lexicon('label_common_Hidden', 'Hidden') }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        @can('manage_forms')
                                            <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                                <x-button type="button"
                                                          variant="ghost"
                                                          size="sm"
                                                          icon="bi-pencil"
                                                          :tooltip="lexicon('tooltip_FormsHub_EditForm', 'Edit form details')"
                                                          @click="openForm('edit', {{ Js::from([
                                                              'id' => $form->id,
                                                              'metadata_title' => $form->metadata_title,
                                                              'is_visible' => (bool) $form->is_visible,
                                                          ]) }})">
                                                    {{ lexicon('btn_common_Edit', 'Edit') }}
                                                </x-button>

                                                <x-button type="button"
                                                          variant="danger"
                                                          size="sm"
                                                          icon="bi-trash"
                                                          :tooltip="lexicon('tooltip_FormsHub_DeleteForm', 'Delete this form')"
                                                          x-data="deleteAction('{{ route('admin.metadata.destroy', $form->id) }}', 'DELETE', '{{ addslashes($form->metadata_title) }}')"
                                                          @click="confirmDelete()">
                                                    {{ lexicon('btn_common_Delete', 'Delete') }}
                                                </x-button>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    </div>
                </x-tab>

                {{-- 2. ALL FORM FIELDS TABLE --}}
                <x-tab type="accordion"
                       :title="lexicon('label_FormsHub_AllFields', 'All Metadata Fields (Inputs, Rich Text & Timelines)')"
                       icon="bi-list-columns-reverse"
                       :open="true">
                    <div id="fields-table-content" x-ref="fieldsTableContent">
                        <x-table type="admin"
                                 :headers="[
                                     lexicon('label_common_FieldName', 'Field Title'),
                                     lexicon('label_common_InputType', 'Input Mode'),
                                     lexicon('label_common_Required', 'Required'),
                                     lexicon('label_common_Status', 'Status'),
                                     lexicon('label_common_Actions', 'Actions')
                                 ]"
                                 :emptyText="lexicon('msg_FormsHub_NoFields', 'No metadata fields exist yet.')">
                            @foreach($fields ?? [] as $field)
                                @php
                                    $rawOpts = $field->metadata_payloads;
                                    if (is_string($rawOpts)) {
                                        $decodedOpts = json_decode($rawOpts, true);
                                        if (is_string($decodedOpts)) {
                                            $decodedOpts = json_decode($decodedOpts, true);
                                        }
                                        $rawOpts = is_array($decodedOpts)
                                            ? $decodedOpts
                                            : array_filter(array_map('trim', explode(',', trim($rawOpts, '"'))));
                                    }
                                    $optsArray = is_array($rawOpts) ? array_values($rawOpts) : [];
                                    $dropdownString = $field->metadata_mode === 'dropdown' ? implode(', ', $optsArray) : '';
                                    $srGroupsArray = $field->metadata_mode === 'service_record' ? $optsArray : [];
                                @endphp
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                    data-search-item
                                    data-metadata-item
                                    data-id="{{ $field->id }}"
                                    data-metadata_title="{{ $field->metadata_title }}"
                                    data-metadata_mode="{{ $field->metadata_mode }}"
                                    x-show="isItemVisible({{ $field->id }})">
                                    <td class="p-4 font-bold text-h">{{ $field->metadata_title }}</td>
                                    <td class="p-4 font-mono text-xs uppercase text-accent">{{ $field->metadata_mode }}</td>
                                    <td class="p-4">
                                        <x-badge type="pill" :variant="$field->is_required ? 'success' : 'muted'">
                                            {{ $field->is_required ? lexicon('label_common_Yes', 'Yes') : lexicon('label_common_No', 'No') }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4">
                                        <x-badge type="pill" :variant="$field->is_visible ? 'success' : 'danger'">
                                            {{ $field->is_visible ? lexicon('label_common_Published', 'Published') : lexicon('label_common_Hidden', 'Hidden') }}
                                        </x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        @can('manage_forms')
                                            <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                                <x-button type="button"
                                                          variant="ghost"
                                                          size="sm"
                                                          icon="bi-pencil"
                                                          :tooltip="lexicon('tooltip_FormsHub_EditField', 'Edit metadata field configuration')"
                                                          @click="openField('edit', {{ Js::from([
                                                              'id' => $field->id,
                                                              'metadata_title' => $field->metadata_title,
                                                              'metadata_mode' => $field->metadata_mode,
                                                              'is_required' => (bool) $field->is_required,
                                                              'metadata_payloads' => $dropdownString,
                                                              'serviceRecordGroups' => $srGroupsArray,
                                                              'linked_metadata' => (string) ($field->linked_metadata ?? ''),
                                                              'is_visible' => (bool) $field->is_visible,
                                                          ]) }})">
                                                    {{ lexicon('btn_common_Edit', 'Edit') }}
                                                </x-button>

                                                <x-button type="button"
                                                          variant="danger"
                                                          size="sm"
                                                          icon="bi-trash"
                                                          :tooltip="lexicon('tooltip_FormsHub_DeleteField', 'Delete this metadata field')"
                                                          x-data="deleteAction('{{ route('admin.metadata.fields.destroy', $field->id) }}', 'DELETE', '{{ addslashes($field->metadata_title) }}')"
                                                          @click="confirmDelete()">
                                                    {{ lexicon('btn_common_Delete', 'Delete') }}
                                                </x-button>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </x-table>
                    </div>
                </x-tab>
            </div>

            {{-- MODAL 1: CREATE / EDIT METADATA FORM --}}
            <x-modal show="modals.form.open" onClose="closeModals()" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.form.mode === 'create'
                        ? '{{ addslashes(lexicon('label_FormsHub_CreateNewFormTitle', 'Create New Metadata Form')) }}'
                        : '{{ addslashes(lexicon('label_FormsHub_EditFormTitle', 'Edit Metadata Form')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.form.action" xMethod="modals.form.method">
                    <x-metadata type="text"
                                name="metadata_title"
                                id="form_metadata_title"
                                xModel="modals.form.metadata_title"
                                :label="lexicon('label_common_FormName', 'Form Title')"
                                :placeholder="lexicon('label_FormsHub_FormNamePlaceholder', 'e.g. Candidate Petition')"
                                :tooltip="lexicon('tooltip_FormsHub_FormNameInput', 'Enter display name for the form')"
                                :required="true" />

                    <input type="hidden" name="is_visible" value="0">
                    <x-metadata type="checkbox"
                                name="is_visible"
                                id="form_is_visible"
                                value="1"
                                xModel="modals.form.is_visible"
                                :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')"
                                :tooltip="lexicon('tooltip_FormsHub_FormVisible', 'Toggle whether this form is active')" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModals()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_FormsHub_SaveForm', 'Save form configuration')">
                            <span x-text="modals.form.mode === 'create'
                                ? '{{ addslashes(lexicon('btn_common_SaveForm', 'Save Form')) }}'
                                : '{{ addslashes(lexicon('btn_common_UpdateForm', 'Update Form')) }}'"></span>
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: CREATE / EDIT METADATA FIELD --}}
            <x-modal show="modals.field.open" onClose="closeModals()" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.field.mode === 'create'
                        ? '{{ addslashes(lexicon('label_FormsHub_AddFormFieldTitle', 'Add Metadata Field')) }}'
                        : '{{ addslashes(lexicon('label_FormsHub_EditFormFieldTitle', 'Edit Metadata Field')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.field.action" xMethod="modals.field.method">
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="text"
                                    name="metadata_title"
                                    id="field_metadata_title"
                                    xModel="modals.field.metadata_title"
                                    :label="lexicon('label_common_FieldName', 'Field Title')"
                                    :placeholder="lexicon('label_FormsHub_FieldNamePlaceholder', 'e.g. Species, Psychological Profile')"
                                    :tooltip="lexicon('tooltip_FormsHub_FieldNameInput', 'Enter display label for the field')"
                                    :required="true" />

                        <x-metadata type="select"
                                    name="metadata_mode"
                                    id="field_metadata_mode"
                                    xModel="modals.field.metadata_mode"
                                    :label="lexicon('label_common_InputType', 'Input Mode')"
                                    :tooltip="lexicon('tooltip_FormsHub_InputTypeSelect', 'Select input component type')"
                                    :required="true">
                            <option value="text">{{ lexicon('label_FormsHub_SingleLineText', 'Single Line Text') }}</option>
                            <option value="textarea">{{ lexicon('label_FormsHub_MultiLineText', 'Multi-line Text Area') }}</option>
                            <option value="richtext">{{ lexicon('label_FormsHub_RichTextEditor', 'Rich Text Editor (TipTap)') }}</option>
                            <option value="dropdown">{{ lexicon('label_FormsHub_DropdownMenu', 'Dropdown Menu') }}</option>
                            <option value="service_record">{{ lexicon('label_FormsHub_ServiceRecordTimeline', 'Service Record Timeline') }}</option>
                        </x-metadata>
                    </div>

                    <x-metadata type="select"
                                name="linked_metadata"
                                id="field_linked_metadata"
                                xModel="modals.field.linked_metadata"
                                :label="lexicon('label_FormsHub_AssignToForm', 'Assign to Form')"
                                :tooltip="lexicon('tooltip_FormsHub_AssignFormSelect', 'Assign field to a target form')"
                                :required="true">
                        <option value="">{{ lexicon('label_common_SelectAForm', '— Select a Form —') }}</option>
                        @foreach($forms ?? [] as $f)
                            <option value="{{ $f->id }}">{{ $f->metadata_title }}</option>
                        @endforeach
                    </x-metadata>

                    {{-- Dropdown Options Input --}}
                    <div x-show="modals.field.metadata_mode === 'dropdown'" x-cloak class="flex flex-col gap-xs">
                        <x-metadata type="text"
                                    name="metadata_payloads"
                                    id="field_metadata_payloads"
                                    xModel="modals.field.metadata_payloads"
                                    :label="lexicon('label_FormsHub_DropdownOptions', 'Dropdown Options')"
                                    :placeholder="lexicon('label_FormsHub_DropdownPlaceholder', 'Option 1, Option 2, Option 3')"
                                    :tooltip="lexicon('tooltip_FormsHub_OptionsInput', 'Comma-separated options for dropdown menus')"
                                    x-bind:disabled="modals.field.metadata_mode !== 'dropdown'" />
                        <span class="text-xs text-muted">{{ lexicon('msg_FormsHub_DropdownHelp', 'Separate dropdown choices with a comma.') }}</span>
                    </div>

                    {{-- Service Record Optgroup Checkboxes --}}
                    <div x-show="modals.field.metadata_mode === 'service_record'" x-cloak class="flex flex-col gap-sm">
                        <span class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_FormsHub_ServiceRecordGroups', 'Allowed Event Optgroups') }}
                        </span>
                        <div class="grid grid-cols-1 md-grid-cols-2 gap-sm bg-panel-sub border-panel border-rounded p-3">
                            <label class="flex flex-row items-center gap-sm cursor-pointer">
                                <input type="checkbox"
                                       name="service_record_groups[]"
                                       value="career"
                                       x-model="modals.field.serviceRecordGroups"
                                       class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                                <span class="text-xs text-p">{{ lexicon('label_ServiceRecords_GroupCareer', 'Career & Duty') }}</span>
                            </label>
                            <label class="flex flex-row items-center gap-sm cursor-pointer">
                                <input type="checkbox"
                                       name="service_record_groups[]"
                                       value="biographical"
                                       x-model="modals.field.serviceRecordGroups"
                                       class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                                <span class="text-xs text-p">{{ lexicon('label_ServiceRecords_GroupBiographical', 'Biographical History') }}</span>
                            </label>
                            <label class="flex flex-row items-center gap-sm cursor-pointer">
                                <input type="checkbox"
                                       name="service_record_groups[]"
                                       value="personal"
                                       x-model="modals.field.serviceRecordGroups"
                                       class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                                <span class="text-xs text-p">{{ lexicon('label_ServiceRecords_GroupPersonal', 'Personal & Family') }}</span>
                            </label>
                            <label class="flex flex-row items-center gap-sm cursor-pointer">
                                <input type="checkbox"
                                       name="service_record_groups[]"
                                       value="medical_factional"
                                       x-model="modals.field.serviceRecordGroups"
                                       class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                                <span class="text-xs text-p">{{ lexicon('label_ServiceRecords_GroupMedicalFactional', 'Medical & Factional') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-col gap-sm pt-2">
                        <input type="hidden" name="is_visible" value="0">
                        <x-metadata type="checkbox"
                                    name="is_visible"
                                    id="field_is_visible"
                                    value="1"
                                    xModel="modals.field.is_visible"
                                    :label="lexicon('label_common_ShowOnSite', 'Show on Site (Visible)')" />

                        <input type="hidden" name="is_required" value="0">
                        <x-metadata type="checkbox"
                                    name="is_required"
                                    id="field-required"
                                    value="1"
                                    xModel="modals.field.is_required"
                                    :label="lexicon('label_FormsHub_MakeRequired', 'Make this a required field')"
                                    :tooltip="lexicon('tooltip_FormsHub_RequiredToggle', 'Mark field as mandatory for submissions')" />
                    </div>

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModals()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_FormsHub_SaveField', 'Save form field configuration')">
                            <span x-text="modals.field.mode === 'create'
                                ? '{{ addslashes(lexicon('btn_common_SaveField', 'Save Field')) }}'
                                : '{{ addslashes(lexicon('btn_common_UpdateField', 'Update Field')) }}'"></span>
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>
        </x-slot:bottom>
    </x-grid-wrapper>
</div>
@endsection