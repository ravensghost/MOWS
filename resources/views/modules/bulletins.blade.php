{{-- 
@WidgetManifest({
    "name": "Station Bulletins",
    "description": "Displays recent bulletins and announcements.",
    "icon": "bi-megaphone"
})
--}}
<div class="flex flex-col gap-md w-full mb-6" x-data>
    <span class="text-2xl font-bold text-h block pb-2">{{ $module->module_name }}</span>
    @forelse($bulletins as $bulletin)
        <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden hover-border-accent transition-colors">
            <div class="p-panel flex flex-col flex-grow gap-sm">
                <span class="text-h text-xl font-bold block">{{ $bulletin->bulletin_title }}</span>
                
                <span class="text-muted text-xs block pb-2"
                      aria-label="{{ lexicon('tooltip_Bulletins_PublishedDate', 'Date this bulletin was broadcast') }}"
                      x-tooltip="'{{ addslashes(lexicon('tooltip_Bulletins_PublishedDate', 'Date this bulletin was broadcast')) }}'">
                    <i class="bi bi-calendar-event mr-1" aria-hidden="true"></i>
                    {{ lexicon('label_common_PostedBy', 'Posted by') }} {{ $bulletin->author->name ?? lexicon('label_common_Admin', 'Admin') }} {{ lexicon('label_common_On', 'on') }} {{ \Carbon\Carbon::parse($bulletin->published_at)->format('M d, Y') }}
                </span>
                
                <div class="text-p tiptap-content">
                    {!! \Stevebauman\Purify\Facades\Purify::clean($bulletin->bulletin_text) !!}
                </div>
            </div>
        </div>
    @empty
        <div class="bg-panel-sub border-dashed-panel rounded-panel p-panel text-center">
            <span class="text-muted italic">{{ lexicon('msg_common_NoActiveBulletins', 'No active bulletins.') }}</span>
        </div>
    @endforelse
</div>