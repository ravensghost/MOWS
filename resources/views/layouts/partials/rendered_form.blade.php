@php
    $fields = \App\Models\FormField::where('form_linked', $form->id)->orderBy('order')->get();
    $formTitleKey = 'label_FormsHub_' . \Illuminate\Support\Str::studly($form->name);
@endphp

<x-accordion-panel :title="lexicon($formTitleKey, $form->name)" :expanded="true">
    <form method="POST" action="/form/submit/{{ $form->id }}" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
        @csrf

        @forelse($fields as $field)
            <x-dynamic-field :field="$field" />
        @empty
            <span class="italic text-muted p-4 text-center block">{{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}</span>
        @endforelse

        @if($fields->count() > 0)
            <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                <x-button type="submit" variant="primary" x-tooltip="'{{ addslashes(lexicon('tooltip_common_SubmitForm', 'Submit this form')) }}'">
                    {{ lexicon('btn_common_Submit', 'Submit') }}
                </x-button>
            </div>
        @endif
    </form>
</x-accordion-panel>