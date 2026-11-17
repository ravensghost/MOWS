{{-- resources/views/modules/form.blade.php --}}
{{-- @WidgetManifest({ "name": "Custom Form", "description": "Renders a form created in the Metadata & Forms Hub.", "icon": "bi-ui-radios", "fields": { "form_id": { "type": "form", "label": "Select a Form", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $formId = trim((string) ($options['form_id'] ?? ''));

    $formDefinition = $formId !== ''
        ? \App\Models\Metadata::where('meta_mode', 'form')
            ->where('id', $formId)
            ->where('is_visible', true)
            ->first()
        : null;

    $formFields = $formDefinition
        ? \App\Models\Metadata::where('linked_metadata', $formDefinition->id)
            ->where('is_visible', true)
            ->orderBy('metadata_sort_order')
            ->get()
        : collect();

    $formTitle = $formDefinition
        ? lexicon('label_FormsHub_' . \Illuminate\Support\Str::studly($formDefinition->metadata_title), $formDefinition->metadata_title)
        : lexicon('label_FormsHub_DefaultWidgetTitle', 'Custom Form');
@endphp

<x-tab type="accordion"
       :title="$formTitle"
       icon="bi-ui-radios"
       :open="true">
    @if(!$formId || !$formDefinition)
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_FormNotSelected', 'No form has been selected for this module.') }}</span>
        </div>
    @elseif($formFields->isEmpty())
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}</span>
        </div>
    @else
        <x-meta type="dynamic"
                :form="$formDefinition"
                :fields="$formFields"
                :action="'/form/' . $formDefinition->id" />
    @endif
</x-tab>