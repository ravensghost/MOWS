{{-- resources/views/components/dropzone.blade.php --}}
@props([
    'url',
    'id' => 'mows-dropzone',
    'paramName' => 'file',
    'maxFiles' => 1,
    'maxFilesize' => 10,
    'acceptedFiles' => null,
    'params' => [],
    'title' => null,
    'tooltip' => null,
])

@php
    $resolvedTitle = $title ?? lexicon('msg_Dropzone_Subtext', 'Drag and drop files or click to browse');
    $resolvedTooltip = $tooltip ?? $resolvedTitle;
@endphp

<div class="w-full"
     x-data="dropzoneComponent('{{ $url }}', {{ $maxFiles }}, {{ $acceptedFiles ? "'{$acceptedFiles}'" : 'null' }})">
    <div id="{{ $id }}"
         @if($resolvedTooltip)
             x-tooltip="'{{ addslashes($resolvedTooltip) }}'"
             title="{{ $resolvedTooltip }}"
             aria-label="{{ $resolvedTooltip }}"
         @endif
         {{ $attributes->merge(['class' => 'dropzone bg-panel-sub border-dashed-panel border-rounded p-6 flex flex-col items-center justify-center gap-sm cursor-pointer transition-colors hover-border-accent text-center']) }}>
        <div class="dz-message needsclick flex flex-col items-center gap-xs">
            <i class="bi bi-cloud-arrow-up text-4xl text-accent"></i>
            <span class="text-sm font-bold text-p">{{ $resolvedTitle }}</span>
            <span class="text-xs text-muted">{{ lexicon('msg_Dropzone_Subtext', 'Drag and drop files or click to browse') }}</span>
        </div>
    </div>
</div>