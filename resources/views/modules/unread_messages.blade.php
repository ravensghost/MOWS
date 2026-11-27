{{-- resources/views/modules/unread_messages.blade.php --}}
{{-- @WidgetManifest({ "name": "Unread Messages", "description": "Displays the user's unread private messages.", "icon": "bi-envelope", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Unread Correspondence" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));$title = $rawTitle !== '' ?$rawTitle : lexicon('label_UnreadMessages_DefaultTitle', 'Unread Correspondence');

    $unreadThreads =$unreadMessages ?? collect();

    if ($unreadThreads->isEmpty() && auth()->check()) {
        $userId = auth()->id();$unreadThreads = \App\Models\CorrespondenceThread::whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id',$userId);
            })
            ->with(['messages' => function ($q) {$q->latest();
            }, 'participants'])
            ->orderByDesc('updated_at')
            ->get()
            ->filter(function ($thread) use ($userId) {$participant = $thread->participants->firstWhere('user_id',$userId);
                $latestMessage =$thread->messages->first();
                if (!$participant || !$latestMessage) {
                    return false;
                }
                if ((int) $latestMessage->user_id === (int)$userId) {
                    return false;
                }
                return $participant->last_read_at === null || $latestMessage->created_at->gt($participant->last_read_at);
            })
            ->values();
    }
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-envelope"
       :open="true">
    <x-slot:top>
        <x-badge type="pill"
                 :variant="$unreadThreads->isNotEmpty() ? 'warning' : 'muted'"
                 :tooltip="lexicon('tooltip_UnreadMessages_Count', 'Unread conversation threads')">
            {{ $unreadThreads->count() }}
        </x-badge>
    </x-slot:top>

    @if($unreadThreads->isEmpty())
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center flex flex-col items-center justify-center gap-xs">
            <i class="bi bi-envelope-check text-2xl text-muted" aria-hidden="true"></i>
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_UnreadMessages_None', 'You have no unread correspondence.') }}</span>
        </div>
    @else
        <div class="flex flex-col gap-sm" x-data>
            @foreach($unreadThreads as $thread)
                @php
                    $threadMode = strtoupper((string) ($thread->thread_mode ?? 'OOC'));
                    $threadTitle =$thread->thread_title ?? lexicon('label_common_UntitledThread', 'Untitled Correspondence');
                    $latestMsg =$thread->messages?->first();
                    $timestamp =$latestMsg?->created_at
                        ? $latestMsg->created_at->diffForHumans()
                        : ($thread->updated_at ? $thread->updated_at->diffForHumans() : '');
                @endphp

                <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row flex-wrap items-center justify-between gap-sm hover-border-accent transition-colors">
                    <div class="flex flex-col gap-xs">
                        <div class="flex flex-row flex-wrap items-center gap-xs">
                            <x-badge type="pill"
                                     :variant="$threadMode === 'IC' ? 'primary' : 'info'"
                                     :tooltip="lexicon('tooltip_Messenger_ThreadMode', 'Correspondence Mode')">
                                {{ $threadMode }}
                            </x-badge>
                            <span class="text-sm font-bold text-h">{{ $threadTitle }}</span>
                        </div>
                        @if($timestamp)
                            <span class="text-xs text-muted">{{ $timestamp }}</span>
                        @endif
                    </div>

                    <x-button type="button"
                              variant="secondary"
                              size="sm"
                              icon="bi-envelope-open"
                              :tooltip="lexicon('tooltip_UnreadMessages_OpenThread', 'Open this conversation in Correspondence')"
                              @click="$dispatch('open-messenger', { threadId: {{$thread->id }} })">
                        {{ lexicon('btn_common_Read', 'Read') }}
                    </x-button>
                </div>
            @endforeach
        </div>
    @endif
</x-tab>