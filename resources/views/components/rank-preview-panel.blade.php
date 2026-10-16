@props([
    'title' => lexicon('label_ranks_LivePreview', 'Live Preview'),
])

<div class="lg-col-span-4 bg-panel-sub border border-panel rounded-panel shadow-panel flex flex-col items-center justify-center p-4 w-full">
    <span class="text-xs text-muted font-bold uppercase tracking-wider mb-4 block">
        {{ $title }}
    </span>
    <x-rank-badge :reactive="true"/>
</div>