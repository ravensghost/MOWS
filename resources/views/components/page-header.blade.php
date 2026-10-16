@props([
    'title',
    'xData' => null,
    'showBack' => null,
])

@php
    $isAdmin = request()->is('admin*');
    $isRootHub = request()->is('dashboard') || request()->is('admin');
    $shouldShowBack = $showBack ?? (!$isRootHub && ($isAdmin || auth()->check()));

    $backUrl = $isAdmin ? '/admin' : '/dashboard';
    $backText = $isAdmin
        ? lexicon('btn_common_BackToAdmin', 'Back to Command Centre')
        : lexicon('btn_common_BackToHub', 'Back to Hub');
    $backTooltip = $isAdmin
        ? lexicon('tooltip_common_BackToAdmin', 'Return to the Command Centre')
        : lexicon('tooltip_common_BackToHub', 'Return to your Dashboard');
@endphp

<div @if($xData) x-data="{{ $xData }}" @endif {{ $attributes->merge(['class' => 'pb-4 mb-4 flex flex-row justify-between items-center flex-wrap gap-md']) }}>
    <span class="text-3xl font-bold text-accent block">{{ $title }}</span>
    
    <div class="flex flex-row flex-wrap gap-md items-center">
        {{-- Slot for custom action buttons (Create New Form, Sync, etc.) --}}
        @isset($actions)
            {{ $actions }}
        @endisset

        @if($shouldShowBack)
            <x-button variant="warning" :href="$backUrl" class="text-sm" x-tooltip="'{{ addslashes($backTooltip) }}'">
                &larr; {{ $backText }}
            </x-button>
        @endif
    </div>
</div>