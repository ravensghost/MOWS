@extends('layouts.app')

@php
    $studlyType = \Illuminate\Support\Str::studly($type);
    $formKey = 'public_label_' . $studlyType . 'Form';
    $formTitle = ucfirst($type) . ' Form';
@endphp

@section('title', lexicon($formKey, $formTitle))

@section('content')
<div x-data @ajax-success.window="$refs.dynamicForm && $refs.dynamicForm.reset()">
    
    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon($formKey, $formTitle)" />

    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <!-- Form Panel -->
            <x-accordion-panel :title="lexicon($formKey, $formTitle)" :expanded="true">
                <form method="POST"
                      action="/form/submit/{{ $type }}"
                      x-data="ajaxForm"
                      x-ref="dynamicForm"
                      @submit.prevent="submit"
                      data-loading-title="{{ lexicon('msg_common_SubmittingForm', 'Submitting form...') }}"
                      class="flex flex-col gap-md">
                    @csrf

                    <!-- Render Fields dynamically -->
                    @forelse($fields as $field)
                        <x-dynamic-field :field="$field" />
                    @empty
                        <span class="italic text-muted p-4 text-center block">{{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}</span>
                    @endforelse

                    <!-- Form Actions -->
                    @if($fields->count() > 0)
                        <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                            <x-button type="submit"
                                      variant="primary"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_common_SubmitForm', 'Submit this form')) }}'">
                                {{ lexicon('btn_common_Submit', 'Submit') }}
                            </x-button>
                        </div>
                    @endif
                    
                </form>
            </x-accordion-panel>
        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection