{{-- resources/views/layouts/partials/rendered_form.blade.php --}}
@php
    $formId = $form->id ?? null;
    $rawFormTitle = $form->metadata_title ?? ($form->name ?? 'Form');
    $formTitleKey = 'label_FormsHub_' . \Illuminate\Support\Str::studly($rawFormTitle);
    $fields = $formId
        ? \App\Models\Metadata::where('linked_metadata', $formId)
            ->where('is_visible', true)
            ->orderBy('metadata_sort_order')
            ->get()
        : collect();
@endphp

<div data-global-sync="rendered-form-{{ $formId }}">
    <x-tab type="accordion"
           :title="lexicon($formTitleKey, $rawFormTitle)"
           icon="bi-ui-checks"
           :open="true">
        <form method="POST"
              action="/form/submit/{{ $formId }}"
              x-data="ajaxForm"
              @submit.prevent="submit"
              data-loading-title="{{ lexicon('msg_common_SubmittingForm', 'Submitting form...') }}"
              class="flex flex-col gap-md">
            @csrf

            @forelse($fields as $field)
                <x-metadata type="dynamic" :field="$field" />
            @empty
                <span class="font-italic text-muted p-4 text-center block">
                    {{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}
                </span>
            @endforelse

            @if(count($fields) > 0)
                <div class="border-t-actions pt-4 text-right flex flex-row flex-wrap items-center justify-end gap-sm">
                    <x-button type="submit"
                              variant="primary"
                              :tooltip="lexicon('tooltip_common_SubmitForm', 'Submit this form')">
                        {{ lexicon('btn_common_Submit', 'Submit') }}
                    </x-button>
                </div>
            @endif
        </form>
    </x-tab>
</div>