{{-- resources/views/layouts/partials/footer.blade.php --}}
<footer data-global-sync="footer" class="bg-panel-header border-t-panel p-4 mt-auto w-full">
    <div class="w-full max-w-container mx-auto px-container flex flex-row flex-wrap items-center justify-between gap-md">
        {{-- Real World & Game System Dates --}}
        <span class="text-sm text-muted flex flex-row flex-wrap items-center gap-xs">
            <span>📅 {{ now()->format('M d, Y') }}</span>
            <span>/</span>
            @php
                $sd = function_exists('world_date') ? world_date(now()) : now()->format('Y-m-d');
                $mappedReal = function_exists('real_date') ? real_date($sd) : now()->format('M d, Y');
            @endphp
            <span class="font-bold text-h">⭐ {{ $sd }}</span>
            <span>/</span>
            <span>🎮 {{ $mappedReal }}</span>
        </span>

        @if(config('app.debug'))
            @php
                $hitData = session('debug_hits', ['queries' => 0, 'total_hits' => 1]);
            @endphp
            <span class="text-sm text-warning font-mono cursor-pointer"
                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_HitMonitor', 'InfinityFree Asset & Hit Monitor')) }}'"
                  title="{{ lexicon('tooltip_common_HitMonitor', 'InfinityFree Asset & Hit Monitor') }}"
                  aria-label="{{ lexicon('tooltip_common_HitMonitor', 'InfinityFree Asset & Hit Monitor') }}">
                ⚡ {{ lexicon('label_common_EstHits', 'Est. Hits:') }} <span class="font-bold">{{ $hitData['total_hits'] }}</span>
                (Q: {{ $hitData['queries'] }} | CSS: {{ $hitData['css'] ?? 0 }} | JS: {{ $hitData['js'] ?? 0 }} | Img: {{ $hitData['img'] ?? 0 }}) |
                {{ lexicon('label_common_LoadTime', 'Load:') }} {{ defined('LARAVEL_START') ? number_format((microtime(true) - LARAVEL_START), 2) : '0.00' }}s
            </span>
        @endif

        <span class="text-sm text-muted flex flex-row flex-wrap items-center gap-xs">
            <a href="/terms"
               class="text-muted hover-text-h no-underline transition-colors"
               x-tooltip="'{{ addslashes(lexicon('tooltip_common_ViewTerms', 'View Terms of Service')) }}'"
               title="{{ lexicon('tooltip_common_ViewTerms', 'View Terms of Service') }}">
                {{ lexicon('public_label_TermsOfService', 'Terms of Service') }}
            </a>
            <span>|</span>
            <a href="/privacy"
               class="text-muted hover-text-h no-underline transition-colors"
               x-tooltip="'{{ addslashes(lexicon('tooltip_common_ViewPrivacy', 'View Privacy Policy')) }}'"
               title="{{ lexicon('tooltip_common_ViewPrivacy', 'View Privacy Policy') }}">
                {{ lexicon('public_label_PrivacyPolicy', 'Privacy Policy') }}
            </a>
            <span>|</span>
            <a href="/credits"
               class="text-muted hover-text-h no-underline transition-colors"
               x-tooltip="'{{ addslashes(lexicon('tooltip_common_ViewCredits', 'View Site Credits')) }}'"
               title="{{ lexicon('tooltip_common_ViewCredits', 'View Site Credits') }}">
                {{ lexicon('public_label_Credits', 'Credits') }}
            </a>
        </span>
    </div>
</footer>