<?php

use App\Models\Term;
use App\Models\TimeSystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use SteveBauman\Purify\Facades\Purify;
use Illuminate\Support\Str;
use Carbon\Carbon;

if (! function_exists('lexicon')) {
    function lexicon(string $key, ?string $default = ''): string
    {
        static $memoryCache = null;
        static $lowerKeyMap = null;

        $locale = strtolower(App::getLocale() ?: 'gb');
        $default = $default ?? '';

        // 1. Fetch raw array dictionary from Cache (No Eloquent collections to break unserialize)
        if ($memoryCache === null) {
            $memoryCache = Cache::rememberForever('mows_lexicon', function () {
                return DB::table('terms')->pluck('translations', 'code_key')->toArray();
            });
            $lowerKeyMap = [];
            foreach (array_keys($memoryCache) as $k) {
                $lowerKeyMap[strtolower($k)] = $k;
            }
        }

        // Case-insensitive key resolution to match MySQL utf8mb4_unicode_ci behaviour
        $actualKey = $key;
        if (! array_key_exists($actualKey, $memoryCache)) {
            $lowerLookup = strtolower($key);
            if (isset($lowerKeyMap[$lowerLookup])) {
                $actualKey = $lowerKeyMap[$lowerLookup];
            }
        }

        // 2. Auto-Discovery for missing terms (Safely cached in memory for the rest of this request)
        if (! array_key_exists($actualKey, $memoryCache)) {
            if (str_starts_with($key, 'public_label_')) {
                $masterGroup = 'public_label';
                $subGroup = substr($key, 13) ?: 'general';
            } elseif (str_starts_with($key, 'admin_label_')) {
                $masterGroup = 'admin_label';
                $subGroup = substr($key, 12) ?: 'general';
            } else {
                $parts = explode('_', $key);
                $masterGroup = count($parts) > 0 ? $parts[0] : 'general';
                $subGroup = count($parts) > 1 ? $parts[1] : 'general';
            }

            $defaultTranslations = ['gb' => $default];

            try {
                // Includes display_value to satisfy strict MySQL table definitions
                DB::table('terms')->insertOrIgnore([
                    'code_key'      => $key,
                    'master_group'  => $masterGroup,
                    'subgroup'      => $subGroup,
                    'display_value' => $default,
                    'translations'  => json_encode($defaultTranslations),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            } catch (\Throwable $e) {
                // Silently avoid breaking the view render if DB insert fails
            }

            // Update in-memory copy so subsequent calls on this page don't re-query
            $memoryCache[$key] = $defaultTranslations;
            $lowerKeyMap[strtolower($key)] = $key;
            Cache::put('mows_lexicon', $memoryCache, 86400);

            return $default;
        }

        $translations = $memoryCache[$actualKey] ?? [];

        // 3. Normalize JSON if stored as string
        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        if (is_array($translations)) {
            $translations = array_change_key_case($translations, CASE_LOWER);
        } else {
            $translations = [];
        }

        // 4. Look up translation with fallbacks
        $value = $translations[$locale] ?? null;

        if ($value === '' || $value === null) {
            $value = $translations['gb'] ?? null;
        }

        // 5. Intelligent Fallback Upgrade Logic
        // If the stored value perfectly matches the generic Str::headline generation,
        // and we now have a better, different fallback string, upgrade the database.
        if ($value && !empty($default) && $value !== $default) {
            $headlineMatch = Str::headline($actualKey);
            $navMatch = Str::headline(str_replace('nav_link_', '', $actualKey));

            if ($value === $headlineMatch || $value === $navMatch) {
                $translations['gb'] = $default;
                $value = $default;

                try {
                    DB::table('terms')
                        ->where('code_key', $actualKey)
                        ->update([
                            'display_value' => $default,
                            'translations'  => json_encode($translations),
                            'updated_at'    => now(),
                        ]);
                } catch (\Throwable $e) {
                    // Silently avoid breaking the view render
                }

                // Push upgraded term back into the cache
                $memoryCache[$actualKey] = $translations;
                Cache::put('mows_lexicon', $memoryCache, 86400);
            }
        }

        return $value ?: $default;
    }
}

if (! function_exists('markdown')) {
    /**
     * Convert raw markdown into safe, sanitized HTML using CommonMark and Purify.
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
     * Resolve the active lexicon locale based on user preference or global settings.
     */
    function resolve_lexicon_locale(): string
    {
        if (auth()->check() && !empty(auth()->user()->lexicon_version)) {
            return auth()->user()->lexicon_version;
        }

        $globalSetting = Cache::rememberForever('global_lexicon_setting', function () {
            return DB::table('settings')->where('key', 'global_lexicon_setting')->value('value');
        });

        if (! $globalSetting || $globalSetting === 'auto') {
            $lexicon = Cache::get('mows_lexicon', []);
            $availableLocales = [];

            foreach ($lexicon as $translations) {
                if (is_string($translations)) {
                    $translations = json_decode($translations, true) ?? [];
                }

                if (is_array($translations)) {
                    foreach (array_keys($translations) as $lang) {
                        $availableLocales[$lang] = true;
                    }
                }
            }

            $availableLocales = array_keys($availableLocales);

            return request()->getPreferredLanguage($availableLocales) ?? config('app.fallback_locale', 'gb');
        }

        return $globalSetting;
    }
}

if (! function_exists('world_date')) {
    function world_date($date, $fallbackFormat = 'M d, Y H:i')
    {
        if (!$date) return '';

        $carbonDate = Carbon::parse($date);

        // Cache the data as an array to completely bypass object serialization timing issues
        $system = Cache::remember('active_time_system', 3600, function () {
            $activeId = DB::table('settings')->where('key', 'universe_time_system')->value('value');
            if (!$activeId) return null;
            $ts = TimeSystem::find($activeId);
            return $ts ? $ts->toArray() : null;
        });

        // Fallback to real world if no system is configured
        if (!$system || $system['engine_type'] === 'real_world') {
            return $carbonDate->format($fallbackFormat);
        }

        $prefix = $system['prefix'] ?? '';
        $suffix = $system['suffix'] ?? '';
        $baseEarthYear = $system['base_earth_year'] ?? $carbonDate->year;
        $baseValue = $system['base_system_value'] ?? 0;
        $multiplier = $system['multiplier'] ?? 1;
        $engineType = $system['engine_type'] ?? 'real_world';

        if ($engineType === 'fractional') {
            // For systems like Stardates: Uses exact fraction of the year
            $yearDiff = ($carbonDate->year - $baseEarthYear) * $multiplier;
            $dayFraction = ($carbonDate->dayOfYear / $carbonDate->daysInYear) * $multiplier;
            $calculatedValue = $baseValue + $yearDiff + $dayFraction;

            // Automatically ensures a space exists if the prefix doesn't already end with whitespace
            $safePrefix = (str_ends_with($prefix, ' ') || $prefix === '') ? $prefix : $prefix . ' ';

            return $safePrefix . number_format($calculatedValue, 1, '.', '') . $suffix;
        }

        if ($engineType === 'offset') {
            // For systems like Fantasy Epochs: Just shifts the year
            $year = $carbonDate->year - ($baseEarthYear - $baseValue);
            $output = $prefix . $year . $suffix;
            return str_replace('{day}', $carbonDate->dayOfYear, $output);
        }

        return $carbonDate->format($fallbackFormat);
    }
    if (! function_exists('get_widget_manifest')) {
    /**
     * Get the widget manifest cache, automatically self-healing/scanning if empty.
     */
    function get_widget_manifest(): array
    {
        return Cache::rememberForever('mows_widget_manifest', function () {
            $widgetPath = resource_path('views/widgets');

            if (! File::exists($widgetPath)) {
                return [];
            }

            $widgetFiles = File::files($widgetPath);
            $discoveredWidgets = [];

            foreach ($widgetFiles as $file) {
                $content = File::get($file);

                if (preg_match('/@WidgetManifest\((.*?)\)\s*--\}\}/s', $content, $matches)) {
                    $manifest = json_decode(trim($matches[1]), true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $widgetKey = str_replace('.blade.php', '', $file->getFilename());
                        $discoveredWidgets[$widgetKey] = $manifest;

                        $defaultOptions = [];
                        if (isset($manifest['fields'])) {
                            foreach ($manifest['fields'] as $key => $field) {
                                if (isset($field['default'])) {
                                    $defaultOptions[$key] = $field['default'];
                                }
                            }
                        }

                        \App\Models\Widget::updateOrCreate(
                            ['widget_key' => $widgetKey],
                            [
                                'name' => $manifest['name'] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $widgetKey)),
                                'default_options' => json_encode($defaultOptions)
                            ]
                        );
                    }
                }
            }

            return $discoveredWidgets;
        });
    }
}
}
