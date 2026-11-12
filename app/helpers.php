<?php

use App\Models\ChronologicalEngine;
use App\Models\Configuration;
use App\Models\LexiconEntry;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Stevebauman\Purify\Facades\Purify;

if (! function_exists('lexicon')) {
    function lexicon(string $semanticToken, ?string $default = ''): string
    {
        static $memoryCache = null;
        static $lowerTokenMap = null;

        $locale = strtolower(App::getLocale() ?: 'gb');
        $default =$default ?? '';

        // 1. Fetch raw array dictionary from Cache
        if ($memoryCache === null) {
            $memoryCache = Cache::rememberForever('mows_lexicon', function () { return DB::table('lexicon_codex')->pluck('translation_payloads', 'semantic_token')->toArray(); });$lowerTokenMap = [];
            foreach (array_keys($memoryCache) as $k) {$lowerTokenMap[strtolower($k)] =$k;
            }
        }

        // Case-insensitive token resolution
        $actualToken =$semanticToken;
        if (! array_key_exists($actualToken,$memoryCache)) {
            $lowerLookup = strtolower($semanticToken);
            if (isset($lowerTokenMap[$lowerLookup])) {$actualToken = $lowerTokenMap[$lowerLookup];
            }
        }

        // 2. Auto-Discovery for missing terms
        if (! array_key_exists($actualToken,$memoryCache)) {
            if (str_starts_with($semanticToken, 'public_label_')) {$masterGroup = 'public_label';
                $subGroup = substr($semanticToken, 13) ?: 'general';
            } elseif (str_starts_with($semanticToken, 'admin_label_')) {$masterGroup = 'admin_label';
                $subGroup = substr($semanticToken, 12) ?: 'general';
            } else {
                $parts = explode('_',$semanticToken);
                $masterGroup = count($parts) > 0 ? $parts[0] : 'general';$subGroup = count($parts) > 1 ?$parts[1] : 'general';
            }

            $defaultTranslations = ['gb' =>$default];

            try {
                DB::table('lexicon_codex')->insertOrIgnore([
                    'semantic_token'         => $semanticToken,
                    'prefix_group_title'     => $masterGroup,
                    'group_title'            => $subGroup,
                    'default_lexicon_string' => $default,
                    'translation_payloads'   => json_encode($defaultTranslations),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            } catch (\Throwable $e) {
                // Silently avoid breaking the view render if DB insert fails
            }

            $memoryCache[$semanticToken] =$defaultTranslations;
            $lowerTokenMap[strtolower($semanticToken)] =$semanticToken;
            Cache::put('mows_lexicon', $memoryCache, 86400);

            return $default;
        }

        $translations = $memoryCache[$actualToken] ?? [];

        // 3. Normalise JSON if stored as string
        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        if (is_array($translations)) {
            $translations = array_change_key_case($translations, CASE_LOWER);
        } else {
            $translations = [];
        }

        // 4. Look up translation with fallbacks
        $resolvedPayload = $translations[$locale] ?? null;

        if ($resolvedPayload === '' ||$resolvedPayload === null) {
            $resolvedPayload =$translations['gb'] ?? null;
        }

        // 5. Intelligent Fallback Upgrade Logic
        if ($resolvedPayload && !empty($default) && $resolvedPayload !==$default) {
            $headlineMatch = Str::headline($actualToken);
            $navMatch = Str::headline(str_replace('nav_link_', '',$actualToken));

            if ($resolvedPayload ===$headlineMatch || $resolvedPayload ===$navMatch) {
                $translations['gb'] =$default;
                $resolvedPayload =$default;

                try {
                    DB::table('lexicon_codex')
                        ->where('semantic_token', $actualToken)
                        ->update([
                            'default_lexicon_string' => $default,
                            'translation_payloads'   => json_encode($translations),
                            'updated_at'             => now(),
                        ]);
                } catch (\Throwable $e) {
                    // Silently avoid breaking the view render
                }

                $memoryCache[$actualToken] =$translations;
                Cache::put('mows_lexicon', $memoryCache, 86400);
            }
        }

        return $resolvedPayload ?: $default;
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
            'html_input' => 'allow',
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
        if (auth()->check() && !empty(auth()->user()->lexicon_version)) {
            return auth()->user()->lexicon_version;
        }

        $globalConfiguration = Cache::rememberForever('global_lexicon_setting', function () {
            return Configuration::where('configuration_token', 'global_lexicon_setting')
                ->pluck('configuration_payload')
                ->first();
        });

        if (! $globalConfiguration ||$globalConfiguration === 'auto') {
            $lexicon = Cache::get('mows_lexicon', []);$availableLocales = [];

            foreach ($lexicon as$translations) {
                if (is_string($translations)) {
                    $translations = json_decode($translations, true) ?? [];
                }

                if (is_array($translations)) {
                    foreach (array_keys($translations) as$lang) {
                        $availableLocales[$lang] = true;
                    }
                }
            }

            $availableLocales = array_keys($availableLocales);

            return request()->getPreferredLanguage($availableLocales) ?? config('app.fallback_locale', 'gb');
        }

        return $globalConfiguration;
    }
}

if (! function_exists('get_widget_manifest')) {
    /**
     * Get the module manifest cache, automatically self-healing/scanning if empty.
     */
    function get_widget_manifest(): array
    {
        return Cache::rememberForever('mows_widget_manifest', function () {
            $widgetPath = resource_path('views/widgets');

            if (! File::exists($widgetPath)) {
                return [];
            }

            $widgetFiles = File::files($widgetPath);$discoveredWidgets = [];

            foreach ($widgetFiles as$file) {
                $content = File::get($file);

                if (preg_match('/@WidgetManifest\((.*?)\)\s*--\}\}/s', $content,$matches)) {
                    $manifest = json_decode(trim($matches[1]), true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $moduleToken = str_replace('.blade.php', '', $file->getFilename());$discoveredWidgets[$moduleToken] =$manifest;

                        $defaultConfigurations = [];
                        if (isset($manifest['fields']) && is_array($manifest['fields'])) {
                            foreach ($manifest['fields'] as $fieldToken =>$field) {
                                if (isset($field['default'])) {$defaultConfigurations[$fieldToken] =$field['default'];
                                }
                            }
                        }

                        Module::updateOrCreate(
                            ['module_token' => $moduleToken],
                            [
                                'module_title' => $manifest['module_title'] ?? Str::title(str_replace('_', ' ', $moduleToken)),
                                'module_default_settings' => $defaultConfigurations,
                            ]
                        );
                    }
                }
            }

            return $discoveredWidgets;
        });
    }
}

if (! function_exists('world_date')) {
    function world_date($date,$fallbackFormat = 'M d, Y H:i')
    {
        if (!$date) return '';

        $carbonDate = Carbon::parse($date);

        $system = Cache::remember('active_time_system', 3600, function () {$activeId = Configuration::where('configuration_token', 'universe_time_system')
                ->pluck('configuration_payload')
                ->first();
            if (!$activeId) return null;
            $ts = ChronologicalEngine::find($activeId);
            return $ts ? $ts->toArray() : null;
        });

        if (!$system ||$system['calculation_algorithm'] === 'real_world') {
            return $carbonDate->format($fallbackFormat);
        }

        $prefix = $system['date_prefix'] ?? '';$suffix = $system['date_suffix'] ?? '';$baseEarthYear = $system['base_earth_year'] ?? $carbonDate->year;
        $baseSystemNum =$system['base_system_value'] ?? 0;
        $multiplier =$system['temporal_multiplier'] ?? 1;
        $engineType =$system['calculation_algorithm'] ?? 'real_world';

        if ($engineType === 'fractional') {
            $yearDiff = ($carbonDate->year - $baseEarthYear) *$multiplier;
            $dayFraction = ($carbonDate->dayOfYear / $carbonDate->daysInYear) *$multiplier;
            $calculatedNum =$baseSystemNum + $yearDiff +$dayFraction;

            $safePrefix = (str_ends_with($prefix, ' ') ||$prefix === '') ? $prefix :$prefix . ' ';

            return $safePrefix . number_format($calculatedNum, 1, '.', '') .$suffix;
        }

        if ($engineType === 'offset') {
            $year =$carbonDate->year - $baseEarthYear +$baseSystemNum;
            $output =$prefix . $year .$suffix;
            return str_replace('{day}', $carbonDate->dayOfYear,$output);
        }

        return $carbonDate->format($fallbackFormat);
    }
}

if (! function_exists('real_date')) {
    /**
     * Convert an in-game stardate into its corresponding future in-game calendar date 
     * by applying the base_earth_modifier offset.
     */
    function real_date($stardate, string$fallbackFormat = 'M d, Y')
    {
        if ($stardate === '' ||$stardate === null) return '';

        // Extract numeric figure from strings (e.g. "Stardate 43309.5" -> 43309.5)
        $numericFigure = floatval(preg_replace('/[^0-9.]/', '', (string)$stardate));

        $system = Cache::remember('active_time_system', 3600, function () {$activeId = Configuration::where('configuration_token', 'universe_time_system')
                ->pluck('configuration_payload')
                ->first();
            if (!$activeId) return null;
            $ts = ChronologicalEngine::find($activeId);
            return $ts ? $ts->toArray() : null;
        });

        if (!$system ||$system['calculation_algorithm'] === 'real_world') {
            return Carbon::parse($stardate)->format($fallbackFormat);
        }

        // Base Earth Year PLUS the modifier to land in the correct future era (e.g., TNG 2364+)
        $baseEarthYear = intval($system['base_earth_year'] ?? 1983) + intval($system['base_earth_modifier'] ?? 0);
        $baseSystemNum = floatval($system['base_system_value'] ?? 0);
        $multiplier = floatval($system['temporal_multiplier'] ?? 1);
        $engineType =$system['calculation_algorithm'] ?? 'real_world';

        if ($engineType === 'fractional') {$netFigure = $numericFigure -$baseSystemNum;
            $safeMultiplier = ($multiplier != 0) ?$multiplier : 1;
            $totalYears = $netFigure / $safeMultiplier;

            $yearOffset = floor($totalYears);$fraction = $totalYears -$yearOffset;

            $targetYear = $baseEarthYear + intval($yearOffset);

            $dummyDate = Carbon::createFromDate($targetYear, 1, 1);
            $daysInYear =$dummyDate->daysInYear;
            $dayOfYear = max(1, min($daysInYear, (int) round($fraction * $daysInYear)));

            return $dummyDate->addDays($dayOfYear - 1)->format($fallbackFormat);
        }

        if ($engineType === 'offset') {
            $targetYear =$baseEarthYear - $baseSystemNum +$numericFigure;
            return Carbon::createFromDate((int) $targetYear, 1, 1)->format($fallbackFormat);
        }

        return Carbon::parse($stardate)->format($fallbackFormat);
    }
}