{{-- resources/views/petition_metadata.blade.php --}}
@extends('layouts.app')

@php
    $activeMode = $metaMode ?? 'application';
    $studlyMode = \Illuminate\Support\Str::studly($activeMode);
    $formKey = 'public_label_' . $studlyMode . 'Form';
    $formTitle = ucfirst($activeMode) . ' Form';
    $resolvedFields = $fields ?? collect();
@endphp

@section('title', lexicon($formKey, $formTitle))

@section('content')
    <!-- Top Page Header Bar -->
    <x-page-header :title="lexicon($formKey, $formTitle)" icon="bi-file-earmark-text" />

    <x-grid-wrapper :book="$systemBook">
        <x-slot:content>
            <x-tab type="accordion"
                   :title="lexicon($formKey, $formTitle)"
                   icon="bi-ui-checks"
                   :open="true">
                <form method="POST"
                      action="/form/submit/{{ $activeMode }}"
                      x-data="ajaxForm"
                      x-ref="dynamicForm"
                      @submit.prevent="submit"
                      data-loading-title="{{ lexicon('msg_common_SubmittingForm', 'Submitting form...') }}"
                      class="flex flex-col gap-md">
                    @csrf

                    @forelse($resolvedFields as $field)
                        <x-metadata type="dynamic" :field="$field" />
                    @empty
                        <span class="font-italic text-muted p-4 text-center block">
                            {{ lexicon('msg_common_FormEmpty', 'This form has no fields assigned to it yet.') }}
                        </span>
                    @endforelse

                    @if(count($resolvedFields) > 0)
                        <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap items-center justify-end gap-sm">
                            <x-button type="submit"
                                      variant="primary"
                                      :tooltip="lexicon('tooltip_common_SubmitForm', 'Submit this form')">
                                {{ lexicon('btn_common_Submit', 'Submit') }}
                            </x-button>
                        </div>
                    @endif
                </form>
            </x-tab>
        </x-slot:content>
    </x-grid-wrapper>
@endsection