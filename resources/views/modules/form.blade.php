{{-- 
@WidgetManifest({
    "name": "Custom Form",
    "description": "Renders a form created in the Forms Hub.",
    "icon": "bi-ui-radios",
    "fields": {
        "form_id": {
            "type": "form",
            "label": "Select a Form",
            "default": ""
        }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $formId = trim($options['form_id'] ?? '');
    $formCategory = $formId ? \App\Models\FormField::where('form_type', 'form')
        ->where('id', $formId)
        ->first() : null;
        
    $formFields = $formId ? \App\Models\FormField::where('form_linked', $formId)
        ->orderBy('order')
        ->get() : collect();
@endphp

<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden w-full mb-6" x-data>
    @if($formCategory)
        <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold">
            <span class="text-xl font-bold text-accent">{{ $formCategory->name }}</span>
        </div>
    @endif

    <div class="p-panel flex flex-col flex-grow gap-md text-muted">
        @if(!$formId || !$formCategory)
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
                <span class="text-muted italic">{{ lexicon('msg_common_FormNotSelected', 'No form has been selected for this widget.') }}</span>
            </div>
        @elseif($formFields->isEmpty())
            <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
                <span class="text-muted italic">{{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}</span>
            </div>
        @else
            <form method="POST" action="/form/{{ $formId }}" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                @csrf
                
                @foreach($formFields as $field)
                    <x-dynamic-field :field="$field" />
                @endforeach

                <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                    <x-button type="submit" 
                              variant="primary"
                              aria-label="{{ lexicon('tooltip_common_SubmitForm', 'Submit this form') }}"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_SubmitForm', 'Submit this form')) }}'">
                        {{ lexicon('btn_common_Submit', 'Submit') }}
                    </x-button>
                </div>
            </form>
        @endif
    </div>
</div>