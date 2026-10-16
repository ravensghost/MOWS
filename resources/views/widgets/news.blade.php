{{-- 
@WidgetManifest({
    "name": "Station News",
    "description": "Displays recent news and announcements.",
    "icon": "bi-megaphone"
})
--}}
<div class="flex flex-col gap-md w-full mb-6" x-data>
    <span class="text-2xl font-bold text-h block pb-2">{{ $widget->name }}</span>

    @forelse($newsItems ?? [] as $news)
        <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden hover-border-accent transition-colors">
            <div class="p-panel flex flex-col flex-grow gap-sm">
                <span class="text-h text-xl font-bold block">{{ $news->title }}</span>
                
                <span class="text-muted text-xs block pb-2"
                      aria-label="{{ lexicon('tooltip_News_PublishedDate', 'Date this announcement was broadcast') }}"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_News_PublishedDate', 'Date this announcement was broadcast')) }}'">
                    <i class="bi bi-calendar-event mr-1" aria-hidden="true"></i>
                    {{ lexicon('label_common_PostedBy', 'Posted by') }} {{ $news->author->name ?? lexicon('label_common_Admin', 'Admin') }} {{ lexicon('label_common_On', 'on') }} {{ \Carbon\Carbon::parse($news->published_at)->format('M d, Y') }}
                </span>
                
                <div class="text-p tiptap-content">
                    {!! \Stevebauman\Purify\Facades\Purify::clean($news->content) !!}
                </div>
            </div>
        </div>
    @empty
        <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
            <span class="text-muted italic">{{ lexicon('msg_common_NoActiveAnnouncements', 'No active announcements.') }}</span>
        </div>
    @endforelse
</div>