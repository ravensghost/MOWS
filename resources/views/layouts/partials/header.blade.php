<header data-global-sync="header" class="bg-panel border-b-panel bg-cover bg-no-repeat bg-banner-pos bg-banner-image flex flex-row items-stretch w-full min-h-48 p-1 relative">
    <div class="w-full max-w-container mx-auto px-container flex flex-row flex-wrap gap-md relative">
        <a href="/"
           class="relative w-full h-full block no-underline"
           x-data
           x-tooltip="'{{ addslashes(lexicon('tooltip_common_ReturnHome', 'Return to Landing Page')) }}'"
           aria-label="{{ config('app.name', 'MOWS') }}">
            <img src="{{ asset('skins/' . $currentSkin . '/images/site/logo.png') }}"
                 alt="{{ config('app.name', 'MOWS') }} {{ lexicon('label_common_Logo', 'Logo') }}"
                 class="absolute h-logo w-auto object-contain translate-y-logo translate-x-logo flex-shrink-0 z-20">
            <span class="text-4xl font-bold text-h shadow-text-title absolute translate-y-title translate-x-title">{{ config('app.name', 'MOWS') }}</span>
        </a>
    </div>
</header>