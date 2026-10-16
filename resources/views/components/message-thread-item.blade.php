@props(['thread', 'activeThread' => null])

@php
    $isActive = isset($activeThread) && $activeThread->id === $thread->id;
    $threadTitle = $thread->title ?? lexicon('label_messenger_DirectMessage', 'Direct Message');
@endphp

<button type="button"
        @click="activeTab = 'thread-{{ $thread->id }}'"
        :class="activeTab === 'thread-{{ $thread->id }}' ? 'bg-panel-header text-p border-l-2 border-accent' : 'bg-panel-sub text-p'"
        class="p-3 border border-panel rounded-input hover-bg-panel-hover cursor-pointer w-full text-left flex flex-col gap-xs transition-colors"
        x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_OpenThread', 'Open conversation')) }}'"
        aria-label="{{ $threadTitle }}">
    <div class="flex flex-row justify-between items-center w-full">
        <x-badge :variant="$thread->type === 'IC' ? 'warning' : 'success'"
                 x-tooltip="'{{ addslashes($thread->type === 'IC' ? lexicon('tooltip_messenger_IcThread', 'In-Character Thread') : lexicon('tooltip_messenger_OocThread', 'Out-Of-Character Thread')) }}'">
            {{ $thread->type === 'IC' ? lexicon('label_messenger_BadgeIc', 'IC') : lexicon('label_messenger_BadgeOoc', 'OOC') }}
        </x-badge>
        <span class="text-xs text-muted">{{ $thread->updated_at->diffForHumans() }}</span>
    </div>
    <span class="font-bold text-sm text-h block truncate w-full">{{ $threadTitle }}</span>
    <span class="text-xs text-muted truncate block w-full">
        @foreach($thread->participants as $p)
            {{ $p->character ? $p->character->name : $p->user->name }}@if(!$loop->last), @endif
        @endforeach
    </span>
</button>