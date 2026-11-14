<?php

use App\Models\ChronologicalEngine;
use App\Models\Configuration;
use App\Models\LexiconCodex;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Stevebauman\Purify\Facades\Purify;

if (! function_exists('lexicon')) {
    /**
     * Resolve a semantic token into its localised translation string, auto-seeding missing tokens.
     */
    function lexicon(string $semanticToken, ?string $default = ''): string
    {
        static $memoryCache = null;
        static $lowerTokenMap = null;

        $locale = strtolower(App::getLocale() ?: 'gb');
        $default = $default ?? '';

        // 1. Fetch lexicon dictionary from Cache via LexiconCodex model
        if ($memoryCache === null) {
            $memoryCache = Cache::rememberForever('lexicon_codex', function () {
                return LexiconCodex::query()
                    ->select(['semantic_token', 'default_lexicon_string', 'translation_payloads'])
                    ->get()
                    ->keyBy('semantic_token')
                    ->map(fn(LexiconCodex $entry) => [
                        'default'  => $entry->default_lexicon_string,
                        'payloads' => is_array($entry->translation_payloads) ? $entry->translation_payloads : [],
                    ])
                    ->toArray();
            });

            $lowerTokenMap = [];
            foreach (array_keys($memoryCache) as $tokenKey) {
                $lowerTokenMap[strtolower($tokenKey)] = $tokenKey;
            }
        }

        // 2. Case-insensitive token resolution
        $actualToken = $semanticToken;
        if (! array_key_exists($actualToken, $memoryCache)) {
            $lowerLookup = strtolower($semanticToken);
            if (isset($lowerTokenMap[$lowerLookup])) {
                $actualToken = $lowerTokenMap[$lowerLookup];
            }
        }

        // 3. Auto-discovery for missing tokens
        if (! array_key_exists($actualToken, $memoryCache)) {
            if (str_starts_with($semanticToken, 'public_label_')) {
                $prefixGroup = 'public_label';
                $remainder = substr($semanticToken, 13);
                $parts = $remainder !== '' ? explode('_', $remainder) : [];
                $groupTitle = (! empty($parts[0])) ? $parts[0] : 'NotDefinedRight';
            } elseif (str_starts_with($semanticToken, 'admin_label_')) {
                $prefixGroup = 'admin_label';
                $remainder = substr($semanticToken, 12);
                $parts = $remainder !== '' ? explode('_', $remainder) : [];
                $groupTitle = (! empty($parts[0])) ? $parts[0] : 'NotDefinedRight';
            } else {
                $parts = explode('_', $semanticToken);
                // Standard tokens MUST have at least 3 segments: prefix_group_action
                if (count($parts) >= 3 && ! empty($parts[0]) && ! empty($parts[1])) {
                    $prefixGroup = $parts[0];
                    $groupTitle = $parts[1];
                } else {
                    $prefixGroup = ! empty($parts[0]) ? $parts[0] : 'NotDefinedRight';
                    $groupTitle = 'NotDefinedRight';
                }
            }

            $defaultPayloads = ['gb' => $default];

            try {
                LexiconCodex::firstOrCreate(
                    ['semantic_token' => $semanticToken],
                    [
                        'prefix_group_title'     => $prefixGroup,
                        'group_title'            => $groupTitle,
                        'default_lexicon_string' => $default,
                        'translation_payloads'   => $defaultPayloads,
                    ]
                );
            } catch (\Throwable $e) {
                // Silently avoid breaking the view render if DB write fails
            }

            $memoryCache[$semanticToken] = [
                'default'  => $default,
                'payloads' => $defaultPayloads,
            ];
            $lowerTokenMap[strtolower($semanticToken)] = $semanticToken;
            Cache::forever('lexicon_codex', $memoryCache);

            return $default;
        }

        // 4. Resolve active locale payload with fallback to default_lexicon_string
        $entry = $memoryCache[$actualToken];
        $payloads = isset($entry['payloads']) && is_array($entry['payloads'])
            ? array_change_key_case($entry['payloads'], CASE_LOWER)
            : [];

        $resolvedPayload = $payloads[$locale] ?? null;

        if ($resolvedPayload === '' || $resolvedPayload === null) {
            $resolvedPayload = $payloads['gb'] ?? null;
        }

        if ($resolvedPayload === '' || $resolvedPayload === null) {
            $resolvedPayload = $entry['default'] ?? null;
        }

        return ($resolvedPayload !== '' && $resolvedPayload !== null) ? (string) $resolvedPayload : $default;
    }
}

if (! function_exists('markdown')) {
    /**
     * Convert raw markdown into safe, sanitised HTML using CommonMark and Purify.
     */
    function markdown(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input'         => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $html = (string) $converter->convert($content);

        return Purify::clean($html);
    }
}

if (! function_exists('resolve_lexicon_locale')) {
    /**
     * Resolve the active lexicon locale based on user preference or global configurations.
     */
    function resolve_lexicon_locale(): string
    {
        if (auth()->check() && ! empty(auth()->user()->user_lexicon_setting)) {
            return auth()->user()->user_lexicon_setting;
        }

        $globalConfiguration = Cache::rememberForever('global_lexicon_setting', function () {
            return Configuration::where('configuration_token', 'global_lexicon_setting')
                ->pluck('configuration_payload')
                ->first();
        });

        if (! $globalConfiguration || $globalConfiguration === 'auto') {
            $lexicon = Cache::get('lexicon_codex', []);
            $availableLocales = [];

            foreach ($lexicon as $entry) {
                $payloads = $entry['payloads'] ?? [];
                if (is_array($payloads)) {
                    foreach (array_keys($payloads) as $lang) {
                        $availableLocales[strtolower($lang)] = true;
                    }
                }
            }

            $availableLocales = array_keys($availableLocales);

            return request()->getPreferredLanguage($availableLocales) ?? config('app.fallback_locale', 'gb');
        }

        return $globalConfiguration;
    }
}

if (! function_exists('get_module_manifest')) {
    /**
     * Get the module manifest cache, automatically self-healing/scanning if empty.
     */
    function get_module_manifest(): array
    {
        return Cache::rememberForever('module_manifest', function () {
            $modulePath = resource_path('views/modules');

            if (! File::exists($modulePath)) {
                return [];
            }

            $moduleFiles = File::files($modulePath);
            $discoveredModules = [];

            foreach ($moduleFiles as $file) {
                $content = File::get($file);

                if (preg_match('/@ModuleManifest\((.*?)\)\s*--\}\}/s', $content, $matches)) {
                    $manifest = json_decode(trim($matches[1]), true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $moduleToken = str_replace('.blade.php', '', $file->getFilename());
                        $discoveredModules[$moduleToken] = $manifest;

                        $defaultConfigurations = [];
                        if (isset($manifest['fields']) && is_array($manifest['fields'])) {
                            foreach ($manifest['fields'] as $fieldToken => $field) {
                                if (isset($field['default'])) {
                                    $defaultConfigurations[$fieldToken] = $field['default'];
                                }
                            }
                        }

                        Module::updateOrCreate(
                            ['module_token' => $moduleToken],
                            [
                                'module_title'            => $manifest['module_title'] ?? Str::title(str_replace('_', ' ', $moduleToken)),
                                'module_default_settings' => $defaultConfigurations,
                            ]
                        );
                    }
                }
            }

            return $discoveredModules;
        });
    }
}

if (! function_exists('active_time_system')) {
    /**
     * Retrieve the active chronological engine configuration from cache.
     */
    function active_time_system(): ?array
    {
        return Cache::remember('active_time_system', 3600, function () {
            $activeId = Configuration::where('configuration_token', 'universe_time_system')
                ->pluck('configuration_payload')
                ->first();

            if (! $activeId) {
                return null;
            }

            $engine = ChronologicalEngine::find($activeId);

            return $engine ? $engine->toArray() : null;
        });
    }
}

if (! function_exists('world_date')) {
    /**
     * Convert a real-world date into the active universe's primary chronological format.
     */
    function world_date(mixed $date, string $fallbackFormat = 'M d, Y H:i'): string
    {
        if (! $date) {
            return '';
        }

        $carbonDate = Carbon::parse($date);
        $system = active_time_system();

        if (! $system || ($system['calculation_algorithm'] ?? 'real_world') === 'real_world') {
            return $carbonDate->format($fallbackFormat);
        }

        $prefix = $system['date_prefix'] ?? '';
        $suffix = $system['date_suffix'] ?? '';
        $baseEarthYear = $system['base_earth_year'] ?? $carbonDate->year;
        $baseSystemNum = $system['base_system_value'] ?? 0;
        $multiplier = $system['temporal_multiplier'] ?? 1;
        $engineType = $system['calculation_algorithm'] ?? 'real_world';

        if ($engineType === 'fractional') {
            $yearDiff = ($carbonDate->year - $baseEarthYear) * $multiplier;
            $dayFraction = ($carbonDate->dayOfYear / $carbonDate->daysInYear) * $multiplier;
            $calculatedNum = $baseSystemNum + $yearDiff + $dayFraction;

            $safePrefix = (str_ends_with($prefix, ' ') || $prefix === '') ? $prefix : $prefix . ' ';

            return $safePrefix . number_format($calculatedNum, 1, '.', '') . $suffix;
        }

        if ($engineType === 'offset') {
            $year = $carbonDate->year - $baseEarthYear + $baseSystemNum;
            $output = $prefix . $year . $suffix;

            return str_replace('{day}', (string) $carbonDate->dayOfYear, $output);
        }

        return $carbonDate->format($fallbackFormat);
    }
}

if (! function_exists('real_date')) {
    /**
     * Convert an in-game world_date value into its corresponding lore-era human calendar date.
     */
    function real_date(mixed $worldDate, string $fallbackFormat = 'M d, Y'): string
    {
        if ($worldDate === '' || $worldDate === null) {
            return '';
        }

        $numericFigure = (float) preg_replace('/[^0-9.]/', '', (string) $worldDate);
        $system = active_time_system();

        if (! $system || ($system['calculation_algorithm'] ?? 'real_world') === 'real_world') {
            return Carbon::parse($worldDate)->format($fallbackFormat);
        }

        $baseEarthYear = (int) ($system['base_earth_year'] ?? 1983) + (int) ($system['base_earth_modifier'] ?? 0);
        $baseSystemNum = (float) ($system['base_system_value'] ?? 0);
        $multiplier = (float) ($system['temporal_multiplier'] ?? 1);
        $engineType = $system['calculation_algorithm'] ?? 'real_world';

        if ($engineType === 'fractional') {
            $netFigure = $numericFigure - $baseSystemNum;
            $safeMultiplier = ($multiplier != 0.0) ? $multiplier : 1.0;
            $totalYears = $netFigure / $safeMultiplier;

            $yearOffset = floor($totalYears);
            $fraction = $totalYears - $yearOffset;

            $targetYear = $baseEarthYear + (int) $yearOffset;

            $dummyDate = Carbon::createFromDate($targetYear, 1, 1);
            $daysInYear = $dummyDate->daysInYear;
            $dayOfYear = max(1, min($daysInYear, (int) round($fraction * $daysInYear)));

            return $dummyDate->addDays($dayOfYear - 1)->format($fallbackFormat);
        }

        if ($engineType === 'offset') {
            $targetYear = $baseEarthYear - $baseSystemNum + $numericFigure;

            return Carbon::createFromDate((int) $targetYear, 1, 1)->format($fallbackFormat);
        }

        return Carbon::parse($worldDate)->format($fallbackFormat);
    }
}
