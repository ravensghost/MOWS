{{-- 
@WidgetManifest({
    "name": "Unread Messages",
    "description": "Displays the user's unread private messages.",
    "icon": "bi-envelope",
    "fields": {}
})
--}}
<div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 w-full" x-data>
    <div class="bg-panel-header text-h border-b border-panel p-panel-header font-bold flex-shrink-0 flex flex-row flex-wrap justify-between items-center gap-md">
        <span class="text-lg font-bold text-h">{{ lexicon('label_Dashboard_UnreadMessages', 'Unread Messages') }}</span>
        <x-badge type="success" 
                 variant="success"
                 aria-label="{{ lexicon('tooltip_Dashboard_UnreadCount', 'Total unread messages') }}"
                 x-tooltip="'{{ addslashes(lexicon('tooltip_Dashboard_UnreadCount', 'Total unread messages')) }}'">
            {{ count($unreadMessages ?? []) }}
        </x-badge>
    </div>
    <div class="p-panel flex flex-col flex-grow gap-sm" x-ref="unreadMessagesContainer">
        @forelse($unreadMessages ?? [] as $message)
            <div class="bg-panel-sub border border-panel rounded-panel p-3 flex flex-row flex-wrap justify-between items-center gap-md">
                <div class="flex flex-col gap-xs">
                    <span class="font-bold text-p block">{{ $message->thread->title ?? lexicon('label_messenger_DirectMessage', 'Direct Message') }}</span>
                    <span class="text-xs text-muted block">{{ Str::limit($message->body, 40) }}</span>
                </div>
                <x-button variant="primary" 
                          href="/messenger/{{ $message->thread_id }}" 
                          class="p-btn-sm text-sm"
                          aria-label="{{ lexicon('tooltip_messenger_ViewThread', 'Open message conversation') }}"
                          x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_ViewThread', 'Open message conversation')) }}'">
                    {{ lexicon('btn_common_View', 'View') }}
                </x-button>
            </div>
        @empty
            <div class="p-4 text-center text-muted italic text-sm">
                <span class="block">{{ lexicon('msg_Dashboard_NoUnreadMessages', 'No unread messages.') }}</span>
            </div>
        @endforelse
    </div>
</div>