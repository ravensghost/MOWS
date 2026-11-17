{{-- resources/views/petition_metadata.blade.php --}}
@extends('layouts.app')

@php
    $activeMode = $metaMode ?? 'application';
    $studlyMode = \Illuminate\Support\Str::studly($activeMode);
    $formKey = 'public_label_' . $studlyMode . 'Form';
    $formTitle = ucfirst($activeMode) . ' Form';
@endphp

@section('title', lexicon($formKey, $formTitle))

@section('content')
    <x-page-header :title="lexicon($formKey, $formTitle)" icon="bi-file-earmark-text" />

    <x-grid-wrapper :book="$systemBook">
        <x-slot:content>
            <x-tab type="accordion"
                   :title="lexicon($formKey, $formTitle)"
                   icon="bi-ui-checks"
                   :open="true">
                <x-meta type="dynamic"
                        :activeMode="$activeMode"
                        :fields="$fields ?? collect()" />
            </x-tab>
        </x-slot:content>
    </x-grid-wrapper>
@endsection