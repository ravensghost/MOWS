@props([
    'baseLayer' => '#1a1a1aFF',
    'departmentColor' => '#ffffffFF',
])

<div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col items-center justify-center gap-md w-full">
    <span class="text-xs font-bold text-input-muted uppercase">
        {{ lexicon('label_ranks_LivePreview', 'Live Insignia Preview') }}
    </span>

    <div class="flex items-center justify-center p-4 bg-panel border border-panel rounded-input w-full">
        <x-insignia-badge 
            :baseLayer="$baseLayer" 
            :departmentColor="$departmentColor" 
            :reactive="true" 
        />
    </div>

    <div class="flex flex-col items-center gap-xs text-center">
        <span class="font-bold text-h text-sm" x-text="modals.insignia.insignia_title || '{{ addslashes(lexicon('label_ranks_PreviewTitlePlaceholder', 'Insignia Title')) }}'"></span>
        <span class="text-xs text-muted" x-text="modals.insignia.abbreviated_title ? '(' + modals.insignia.abbreviated_title + ')' : ''"></span>
    </div>
</div>