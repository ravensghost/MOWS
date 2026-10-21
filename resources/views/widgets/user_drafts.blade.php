{{-- 
@WidgetManifest({
    "name": "Draft Chapters",
    "description": "Displays the user's unpublished chapter drafts.",
    "icon": "bi-journal-text",
    "fields": {}
})
--}}
<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 w-full" x-data>
    <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold flex-shrink-0 flex flex-row flex-wrap justify-between items-center gap-md">
        <span class="text-lg font-bold text-h">{{ lexicon('label_Dashboard_DraftChapters', 'Draft Chapters') }}</span>
        <x-badge type="warning" 
                 variant="warning"
                 aria-label="{{ lexicon('tooltip_Dashboard_DraftCount', 'Total unpublished chapter drafts') }}"
                 x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_DraftCount', 'Total unpublished chapter drafts')) }}'">
            {{ count($draftChapters ?? []) }}
        </x-badge>
    </div>
    <div class="p-panel flex flex-col flex-grow gap-sm" x-ref="draftChaptersContainer">
        @forelse($draftChapters ?? [] as $draft)
            <div class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-row flex-wrap justify-between items-center gap-md">
                <div class="flex flex-col gap-xs">
                    <span class="font-bold text-p block">{{ $draft->title ?? lexicon('label_common_UntitledChapter', 'Untitled Chapter') }}</span>
                    <span class="text-xs text-muted block">{{ lexicon('label_common_LastUpdated', 'Updated') }} {{ $draft->updated_at->diffForHumans() }}</span>
                </div>
                <x-button variant="secondary" 
                        type="button"
                        @click="$dispatch('open-chapter-modal', { chapterId: {{ $draft->id }} })" 
                        class="p-btn-sm text-sm"
                        aria-label="{{ lexicon('tooltip_WriteChapter_ResumeDraft', 'Continue writing this draft chapter') }}"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_ResumeDraft', 'Continue writing this draft chapter')) }}'">
                    {{ lexicon('btn_common_Resume', 'Resume') }}
                </x-button>
            </div>
        @empty
            <div class="p-4 text-center text-muted italic text-sm">
                <span class="block">{{ lexicon('msg_Dashboard_NoDrafts', 'No draft chapters found.') }}</span>
            </div>
        @endforelse
    </div>
</div>