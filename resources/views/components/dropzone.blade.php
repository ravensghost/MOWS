@props([
    'url',
    'id' => 'mows-dropzone',
    'maxFiles' => 1,
    'acceptedFiles' => null,
    'title' => lexicon('msg_Dropzone_Subtext', 'Drag and drop files or click to browse')
])

<div class="w-full" x-data="dropzoneComponent('{{ $url }}', {{ $maxFiles }}, {{ $acceptedFiles ? "'{$acceptedFiles}'" : 'null' }})">
    <div id="{{ $id }}" class="dropzone bg-panel-sub border-dashed-panel rounded-panel p-panel text-center cursor-pointer flex flex-col items-center justify-center gap-sm hover:border-accent transition-colors">
        <div class="dz-message needsclick flex flex-col items-center gap-xs">
            <i class="bi bi-cloud-arrow-up text-4xl text-accent"></i>
            <span class="text-sm font-bold text-p">{{ $title }}</span>
            <span class="text-xs text-muted">{{ lexicon('msg_Dropzone_Subtext', 'Drag and drop files or click to browse') }}</span>
        </div>
    </div>
</div>