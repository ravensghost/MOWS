{{-- resources/views/components/page-header.blade.php --}}
@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'xData' => null,
    'showBack' => null,
])

@php
    $isAdmin = request()->is('admin*');
    $isRootHub = request()->is('dashboard') || request()->is('admin') || request()->is('admin/command-centre');
    $shouldShowBack = $showBack ?? (!$isRootHub && ($isAdmin || auth()->check()));

    $backUrl = $isAdmin ? '/admin/command-centre' : '/dashboard';
    $backText = $isAdmin
        ? lexicon('btn_common_BackToAdmin', 'Back to Command Centre')
        : lexicon('btn_common_BackToHub', 'Back to Hub');
    $backTooltip = $isAdmin
        ? lexicon('tooltip_common_BackToAdmin', 'Return to the Command Centre')
        : lexicon('tooltip_common_BackToHub', 'Return to your Dashboard');
@endphp

<div @if($xData) x-data="{{ $xData }}" @endif
     {{ $attributes->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-panel p-panel-header flex flex-row flex-wrap justify-between items-center gap-md mb-4']) }}>

    <div class="flex flex-col gap-xs">
        <h1 class="text-2xl font-bold text-h flex flex-row items-center gap-sm m-0">
            @if($icon)
                <i class="bi {{ $icon }} text-accent"></i>
            @endif
            <span>{{ $title }}</span>
        </h1>

        @if($subtitle)
            <p class="text-sm text-muted m-0">{{ $subtitle }}</p>
        @elseif($slot->isNotEmpty())
            <div class="text-sm text-muted m-0">
                {{ $slot }}
            </div>
        @endif
    </div>

    <div class="flex flex-row flex-wrap items-center gap-sm">
        @isset($actions)
            {{ $actions }}
        @endisset

        @if($shouldShowBack)
            <x-button variant="warning"
                      size="sm"
                      :href="$backUrl"
                      :tooltip="$backTooltip">
                &larr; {{ $backText }}
            </x-button>
        @endif
    </div>
</div>