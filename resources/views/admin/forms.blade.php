@extends('layouts.app')

@section('title', lexicon('admin_label_FormsHub', 'Forms & Component Hub'))

@section('content')
<div x-data="adminModal()"
    @open-form-modal.window="openForm($event.detail.mode, $event.detail)"
    @open-field-modal.window="openField($event.detail.mode, $event.detail)"
    @ajax-success.window="refreshDOM_US()">

    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_FormsHub', 'Forms & Component Hub')">
                <x-slot:actions>
                    @can('manage_forms')
                        <x-button variant="primary" 
                                  href="/admin/pages" 
                                  class="text-sm p-btn-sm"
                                  aria-label="{{ lexicon('tooltip_FormsHub_ManagePages', 'Manage station pages and layouts') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_ManagePages', 'Manage station pages and layouts')) }}'">
                            <i class="bi bi-file-earmark-richtext"></i> {{ lexicon('btn_FormsHub_ManagePages', 'Manage Pages') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:bottom>
            <!-- 1. ALL FORMS TABLE -->
            <x-accordion-panel :title="lexicon('label_FormsHub_AllForms', 'All Forms (Joining, Wiki, etc.)')" :expanded="true">
                <x-slot:top>
                    @can('manage_forms')
                        <x-button type="button" 
                                  variant="success" 
                                  @click="openForm('create')" 
                                  class="text-sm p-btn-sm"
                                  aria-label="{{ lexicon('tooltip_FormsHub_CreateForm', 'Create a new custom form') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_CreateForm', 'Create a new custom form')) }}'">
                            <i class="bi bi-plus-lg"></i> {{ lexicon('btn_FormsHub_CreateNewForm', 'Create New Form') }}
                        </x-button>
                    @endcan
                </x-slot:top>

                <div id="forms-table-content" x-ref="formsTableContent">
                    <x-table class="border-t-0 rounded-none shadow-none">
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_common_FormName', 'Form Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_FormsHub_AttachedFields', 'Attached Fields') }}</th>
                            <th class="p-4 text-right w-48">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>

                        @forelse($forms ?? [] as $form)
                            @php
                                $attachedFields = $fields->where('form_linked',$form->id);
                            @endphp
                            <tr class="border-b-table-row hover-bg-table-row">
                                <td class="p-4 font-bold text-main">{{ $form->name }}</td>
                                <td class="p-4 text-sm text-muted">
                                    @if($attachedFields->count() > 0) {{$attachedFields->pluck('name')->join(', ') }}
                                    @else
                                        <span class="italic opacity-50">{{ lexicon('msg_FormsHub_NoFieldsAssigned', 'No fields assigned') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 text-right flex flex-row justify-end items-center gap-sm text-sm">
                                    @can('manage_forms')
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-warning" 
                                                  @click="openForm('edit', { id: {{ $form->id }}, name: '{{ addslashes($form->name) }}' })"
                                                  aria-label="{{ lexicon('tooltip_FormsHub_EditForm', 'Edit form details') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_EditForm', 'Edit form details')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-danger hover-danger" 
                                                  x-data="deleteAction(`/admin/forms/{{ $form->id }}`, 'DELETE', '{{ addslashes($form->name) }}')" 
                                                  @click="confirmDelete"
                                                  aria-label="{{ lexicon('tooltip_FormsHub_DeleteForm', 'Delete this form') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_DeleteForm', 'Delete this form')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-muted italic">
                                    <span class="block">{{ lexicon('msg_FormsHub_NoForms', 'No custom forms have been established yet.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- 2. ALL FORM FIELDS TABLE -->
            <x-accordion-panel :title="lexicon('label_FormsHub_AllFields', 'All Form Fields (Inputs & Textareas)')" :expanded="true">
                <x-slot:top>
                    @can('manage_forms')
                        <x-button type="button" 
                                  variant="success" 
                                  @click="openField('create')" 
                                  class="text-sm p-btn-sm"
                                  aria-label="{{ lexicon('tooltip_FormsHub_AddField', 'Add a new form input field') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_AddField', 'Add a new form input field')) }}'">
                            <i class="bi bi-plus-lg"></i> {{ lexicon('btn_FormsHub_AddFormField', 'Add Form Field') }}
                        </x-button>
                    @endcan
                </x-slot:top>

                <div id="fields-table-content" x-ref="fieldsTableContent">
                    <x-table class="border-t-0 rounded-none shadow-none">
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_common_FieldName', 'Field Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_common_InputType', 'Input Type') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_common_Required', 'Required') }}</th>
                            <th class="p-4 text-right w-48">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>

                        @forelse($fields ?? [] as $field)
                            @php
                                $safeOptions = str_replace(["\r", "\n"], ['\r', '\n'], addslashes(is_array($field->options) ? implode(',', $field->options) : ($field->options ?? '')));
                            @endphp
                            <tr class="border-b-table-row hover-bg-table-row">
                                <td class="p-4 font-bold text-main">{{ $field->name }}</td>
                                <td class="p-4 text-sm uppercase tracking-wider text-muted">{{ $field->type }}</td>
                                <td class="p-4 text-sm uppercase tracking-wider text-muted">
                                    @if($field->is_required)
                                        <x-badge type="success">{{ lexicon('label_common_Yes', 'Yes') }}</x-badge>
                                    @else
                                        <x-badge type="planned">{{ lexicon('label_common_No', 'No') }}</x-badge>
                                    @endif
                                </td>
                                <td class="p-4 text-right flex flex-row justify-end items-center gap-sm text-sm">
                                    @can('manage_forms')
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-warning" 
                                                  @click="openField('edit', { id: {{ $field->id }}, name: '{{ addslashes($field->name) }}', type: '{{ $field->type }}', isRequired: {{$field->is_required ? 'true' : 'false' }}, options: '{{ $safeOptions }}', formLinked: '{{$field->form_linked }}' })"
                                                  aria-label="{{ lexicon('tooltip_FormsHub_EditField', 'Edit form field configuration') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_EditField', 'Edit form field configuration')) }}'">
                                            <i class="bi bi-pencil"></i> {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-danger hover-danger" 
                                                  x-data="deleteAction(`/admin/forms/fields/{{ $field->id }}`, 'DELETE', '{{ addslashes($field->name) }}')" 
                                                  @click="confirmDelete"
                                                  aria-label="{{ lexicon('tooltip_FormsHub_DeleteField', 'Delete this form field') }}"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_DeleteField', 'Delete this form field')) }}'">
                                            <i class="bi bi-trash"></i> {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-muted italic">
                                    <span class="block">{{ lexicon('msg_FormsHub_NoFields', 'No form fields exist yet.') }}</span>
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- MODAL 1: CREATE / EDIT FORM -->
            <x-modal show="modals.form.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.form.mode === 'create' ? '{{ addslashes(lexicon('label_FormsHub_CreateNewFormTitle', 'Create New Form')) }}' : '{{ addslashes(lexicon('label_FormsHub_EditFormTitle', 'Edit Form')) }}'"></span>
                </x-slot:top>

                <form :action="modals.form.action" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md"
                      data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                    @csrf
                    <input type="hidden" name="_method" :value="modals.form.method">
                    
                    <x-form-input name="name"
                                  id="form_name"
                                  type="text"
                                  :label="lexicon('label_common_FormName', 'Form Name')"
                                  :placeholder="lexicon('label_FormsHub_FormNamePlaceholder', 'e.g. Character Application')"
                                  :tooltip="lexicon('tooltip_FormsHub_FormNameInput', 'Enter display name for the form')"
                                  xModel="modals.form.name"
                                  :required="true" />

                    <div class="border-t-actions pt-6 flex flex-row justify-end gap-sm mt-4">
                        <x-button type="button" 
                                  variant="secondary" 
                                  class="p-btn-sm text-sm"
                                  @click="modals.form.open = false"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit" 
                                  variant="primary" 
                                  class="p-btn-sm text-sm"
                                  x-text="modals.form.mode === 'create' ? '{{ addslashes(lexicon('btn_common_SaveForm', 'Save Form')) }}' : '{{ addslashes(lexicon('btn_common_UpdateForm', 'Update Form')) }}'"
                                  aria-label="{{ lexicon('tooltip_FormsHub_SaveForm', 'Save form configuration') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_SaveForm', 'Save form configuration')) }}'">
                        </x-button>
                    </div>
                </form>
            </x-modal>

            <!-- MODAL 2: CREATE / EDIT FORM FIELD -->
            <x-modal show="modals.field.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.field.mode === 'create' ? '{{ addslashes(lexicon('label_FormsHub_AddFormFieldTitle', 'Add Form Field')) }}' : '{{ addslashes(lexicon('label_FormsHub_EditFormFieldTitle', 'Edit Form Field')) }}'"></span>
                </x-slot:top>

                <form :action="modals.field.action" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md"
                      data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}">
                    @csrf
                    <input type="hidden" name="_method" :value="modals.field.method">
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                        <x-form-input name="name"
                                      id="field_name"
                                      type="text"
                                      :label="lexicon('label_common_FieldName', 'Field Name')"
                                      :placeholder="lexicon('label_FormsHub_FieldNamePlaceholder', 'e.g. Age')"
                                      :tooltip="lexicon('tooltip_FormsHub_FieldNameInput', 'Enter display label for the field')"
                                      xModel="modals.field.name"
                                      :required="true" />

                        <div class="flex flex-col gap-xs">
                            <label for="field_type" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_common_InputType', 'Input Type') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="field_type" 
                                    name="type" 
                                    x-model="modals.field.type" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    aria-label="{{ lexicon('tooltip_FormsHub_InputTypeSelect', 'Select input component type') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_InputTypeSelect', 'Select input component type')) }}'">
                                <option value="text">{{ lexicon('label_FormsHub_SingleLineText', 'Single Line Text') }}</option>
                                <option value="textarea">{{ lexicon('label_FormsHub_MultiLineText', 'Multi-line Text Area') }}</option>
                                <option value="richtext">{{ lexicon('label_FormsHub_RichTextEditor', 'Rich Text Editor (TipTap)') }}</option>
                                <option value="dropdown">{{ lexicon('label_FormsHub_DropdownMenu', 'Dropdown Menu') }}</option>
                                <option value="service_record">{{ lexicon('label_FormsHub_ServiceRecordTimeline', 'Service Record Timeline') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs mt-2">
                        <label for="field_form_linked" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_FormsHub_AssignToForm', 'Assign to Form') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <select id="field_form_linked" 
                                name="form_linked" 
                                x-model="modals.field.formLinked" 
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                required
                                aria-label="{{ lexicon('tooltip_FormsHub_AssignFormSelect', 'Assign field to a target form') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_AssignFormSelect', 'Assign field to a target form')) }}'">
                            <option value="" disabled>{{ lexicon('label_common_SelectAForm', 'Select a Form...') }}</option>
                            @foreach($forms ?? [] as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dropdown Options Input -->
                    <div class="flex flex-col gap-xs mt-2" x-cloak x-show="modals.field.type === 'dropdown'">
                        <x-form-input name="options"
                                      id="field_options"
                                      type="text"
                                      :label="lexicon('label_FormsHub_DropdownOptions', 'Dropdown Options')"
                                      :placeholder="lexicon('label_FormsHub_DropdownPlaceholder', 'Option 1, Option 2, Option 3')"
                                      :tooltip="lexicon('tooltip_FormsHub_OptionsInput', 'Comma-separated options for dropdown menus')"
                                      xModel="modals.field.options" />
                        <span class="text-xs text-muted" x-show="modals.field.mode === 'create'">{{ lexicon('msg_FormsHub_DropdownHelp', 'Only required if using a Dropdown Menu. Separate items with a comma.') }}</span>
                    </div>

                    <!-- Service Record Optgroup Checkboxes -->
                    <div class="flex flex-col gap-sm mt-2" x-cloak x-show="modals.field.type === 'service_record'">
                        <label class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_FormsHub_ServiceRecordGroups', 'Allowed Event Optgroups') }}
                        </label>
                        <div class="grid grid-cols-2 gap-sm bg-panel-sub border border-panel rounded-panel p-3">
                            <div class="flex flex-row items-center gap-sm">
                                <input type="checkbox" value="career" x-model="modals.field.serviceRecordGroups" id="grp_career" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                                <label for="grp_career" class="text-xs text-p cursor-pointer">{{ lexicon('label_ServiceRecords_GroupCareer', 'Career & Duty') }}</label>
                            </div>
                            <div class="flex flex-row items-center gap-sm">
                                <input type="checkbox" value="biographical" x-model="modals.field.serviceRecordGroups" id="grp_bio" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                                <label for="grp_bio" class="text-xs text-p cursor-pointer">{{ lexicon('label_ServiceRecords_GroupBiographical', 'Biographical History') }}</label>
                            </div>
                            <div class="flex flex-row items-center gap-sm">
                                <input type="checkbox" value="personal" x-model="modals.field.serviceRecordGroups" id="grp_pers" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                                <label for="grp_pers" class="text-xs text-p cursor-pointer">{{ lexicon('label_ServiceRecords_GroupPersonal', 'Personal & Family') }}</label>
                            </div>
                            <div class="flex flex-row items-center gap-sm">
                                <input type="checkbox" value="medical_factional" x-model="modals.field.serviceRecordGroups" id="grp_med" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                                <label for="grp_med" class="text-xs text-p cursor-pointer">{{ lexicon('label_ServiceRecords_GroupMedicalFactional', 'Medical & Factional') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-row items-center gap-sm mt-2">
                        <input type="checkbox" name="is_required" id="field-required" x-model="modals.field.isRequired" value="1" class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine cursor-pointer">
                        <label for="field-required" class="text-sm font-bold text-muted cursor-pointer"
                               aria-label="{{ lexicon('tooltip_FormsHub_RequiredToggle', 'Mark field as mandatory for submissions') }}"
                               x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_RequiredToggle', 'Mark field as mandatory for submissions')) }}'">
                            {{ lexicon('label_FormsHub_MakeRequired', 'Make this a required field') }}
                        </label>
                    </div>

                    <div class="border-t-actions pt-6 flex flex-row justify-end gap-sm mt-4">
                        <x-button type="button" 
                                  variant="secondary" 
                                  class="p-btn-sm text-sm"
                                  @click="modals.field.open = false"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit" 
                                  variant="primary" 
                                  class="p-btn-sm text-sm"
                                  x-text="modals.field.mode === 'create' ? '{{ addslashes(lexicon('btn_common_SaveField', 'Save Field')) }}' : '{{ addslashes(lexicon('btn_common_UpdateField', 'Update Field')) }}'"
                                  aria-label="{{ lexicon('tooltip_FormsHub_SaveField', 'Save form field configuration') }}"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_FormsHub_SaveField', 'Save form field configuration')) }}'">
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection