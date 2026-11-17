{{-- resources/views/layouts/partials/header.blade.php --}}
@php
    $currentSkin = $activeSkin?->directory_name ?? ($skinSettings['active_skin'] ?? 'default');
    $siteTitle = \App\Models\Configuration::where('configuration_token', 'site_title')->value('configuration_payload')
        ?: config('app.name', 'MOWS');
@endphp

<header data-global-sync="header" class="w-full bg-banner-image bg-banner-pos bg-cover bg-no-repeat border-b-panel relative overflow-hidden">
    <div class="w-full max-w-container mx-auto px-container h-32 relative flex flex-row items-center justify-between">
        <a href="/"
           class="flex flex-row items-center gap-md no-underline"
           x-tooltip="'{{ addslashes(lexicon('tooltip_common_ReturnHome', 'Return to Home Page')) }}'"
           title="{{ lexicon('tooltip_common_ReturnHome', 'Return to Home Page') }}"
           aria-label="{{ $siteTitle }}">
            <img src="{{ asset('skins/' . $currentSkin . '/images/site/logo.png') }}"
                 alt="{{ $siteTitle }}"
                 class="h-logo w-auto object-contain relative translate-y-logo translate-x-logo">
            <span class="text-3xl font-bold text-h relative translate-y-title translate-x-title">
                {{ lexicon('public_label_SiteTitle', $siteTitle) }}
            </span>
        </a>
    </div>
</header>