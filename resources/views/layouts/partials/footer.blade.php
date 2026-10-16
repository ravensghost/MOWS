<footer class="bg-panel-header border-t border-panel pt-6 pb-4 mt-auto" x-data>
    <div class="w-full max-w-container mx-auto px-container flex flex-row flex-wrap gap-md items-center justify-between">
        <span class="text-sm text-muted">
            &copy; {{ date('Y') }} {{ config('app.name', 'MOWS') }}. {{ lexicon('label_common_AllRightsReserved', 'All rights reserved.') }}
        </span>
        <span class="text-sm text-muted">
            {{ lexicon('label_common_PoweredBy', 'Powered by MOWS Engine') }} v{{ config('app.version', '1.0.0') }}
        </span>
        <span class="text-sm text-muted">
            {{ lexicon('label_common_CurrentSkin', 'Current Skin:') }} {{ $currentSkin }}
        </span>
        <span class="text-sm text-muted">
            {{ lexicon('label_common_CurrentUser', 'Current User:') }} {{ Auth::user()->name ?? lexicon('label_common_Guest', 'Guest') }}
        </span>
        <span class="text-sm text-muted">
            {{ lexicon('label_common_Language', 'Language:') }} {{ app()->getLocale() }}
        </span>

        @if(config('app.debug'))
            @php $hitData = session('debug_hits', ['queries' => 0, 'total_hits' => 1]); @endphp
            <span class="text-sm text-warning font-mono cursor-pointer"
                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_HitMonitor', 'InfinityFree Asset & Hit Monitor')) }}'">
                ⚡ {{ lexicon('label_common_EstHits', 'Est. Hits:') }} <span class="font-bold">{{ $hitData['total_hits'] }}</span> 
                (Q: {{ $hitData['queries'] }} | CSS: {{ $hitData['css'] ?? 0 }} | JS: {{ $hitData['js'] ?? 0 }} | Img: {{ $hitData['img'] ?? 0 }}) | 
                {{ lexicon('label_common_LoadTime', 'Load:') }} {{ number_format((microtime(true) - LARAVEL_START), 2) }}s
            </span>
        @endif

        <span class="text-sm text-muted">
            <a href="/terms" class="text-muted hover-main no-underline" x-tooltip="'{{ addslashes(lexicon('tooltip_common_ViewTerms', 'View Terms of Service')) }}'">{{ lexicon('public_label_TermsOfService', 'Terms of Service') }}</a> |
            <a href="/privacy" class="text-muted hover-main no-underline" x-tooltip="'{{ addslashes(lexicon('tooltip_common_ViewPrivacy', 'View Privacy Policy')) }}'">{{ lexicon('public_label_PrivacyPolicy', 'Privacy Policy') }}</a> |
            <a href="/credits" class="text-muted hover-main no-underline" x-tooltip="'{{ addslashes(lexicon('tooltip_common_ViewCredits', 'View Site Credits')) }}'">{{ lexicon('public_label_Credits', 'Credits') }}</a>
        </span>
    </div>
</footer>