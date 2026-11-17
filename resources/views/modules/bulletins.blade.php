{{-- resources/views/modules/bulletins.blade.php --}}
{{-- @WidgetManifest({ "name": "Station Bulletins", "description": "Displays recent bulletins and announcements.", "icon": "bi-megaphone", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Station Bulletins" }, "limit": { "type": "number", "label": "Number of Bulletins to Show", "default": "5" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Bulletins_DefaultTitle', 'Station Bulletins');
    $limit = max(1, (int) ($options['limit'] ?? 5));

    $bulletinsList = $newsItems
        ?? $bulletins
        ?? \App\Models\Bulletin::with('author')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-megaphone"
       :open="true">
    @forelse($bulletinsList as $bulletin)
        @php
            $authorName = $bulletin->author->user_handle
                ?? $bulletin->author->name
                ?? lexicon('label_common_Admin', 'Admin');
            $publishedDate = $bulletin->published_at
                ? \Carbon\Carbon::parse($bulletin->published_at)->format('M d, Y')
                : ($bulletin->created_at ? $bulletin->created_at->format('M d, Y') : '');
            $dateTooltip = lexicon('tooltip_Bulletins_PublishedDate', 'Date this bulletin was broadcast');
        @endphp

        <x-tab type="sub"
               :title="$bulletin->bulletin_title"
               icon="bi-broadcast">
            <span class="text-muted text-xs flex flex-row flex-wrap items-center gap-xs pb-2 border-b-panel"
                  x-tooltip="'{{ addslashes($dateTooltip) }}'"
                  title="{{ $dateTooltip }}"
                  aria-label="{{ $dateTooltip }}">
                <i class="bi bi-calendar-event text-accent" aria-hidden="true"></i>
                <span>
                    {{ lexicon('label_common_PostedBy', 'Posted by') }}
                    <strong class="text-h">{{ $authorName }}</strong>
                    @if($publishedDate)
                        {{ lexicon('label_common_On', 'on') }} {{ $publishedDate }}
                    @endif
                </span>
            </span>

            <div class="text-sm text-p leading-relaxed tiptap-content">
                {!! \Stevebauman\Purify\Facades\Purify::clean($bulletin->bulletin_text) !!}
            </div>
        </x-tab>
    @empty
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_common_NoActiveBulletins', 'No active bulletins.') }}</span>
        </div>
    @endforelse
</x-tab>