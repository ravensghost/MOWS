<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\AiTranslationService;
use App\Models\Term;
use App\Models\Setting;

class LexiconLanguageController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $request->validate([
            'lang_type' => 'required|in:common,custom',
            'common_lang_code' => 'required_if:lang_type,common|nullable|string|max:5',
            'custom_lang_code' => 'required_if:lang_type,custom|nullable|string|max:10',
            'custom_lang_name' => 'nullable|string|max:255',
        ]);

        $locale = strtolower(trim($request->lang_type === 'common'
            ? $request->common_lang_code
            : $request->custom_lang_code));

        if ($request->lang_type === 'custom' && $request->filled('custom_lang_name')) {
            Setting::updateOrCreate(
                ['key' => 'lexicon_label_' . $locale],
                ['value' => trim($request->custom_lang_name)]
            );
            Cache::forget('global_settings');
        }

        if ($request->lang_type === 'common') {
            $baseTerms = Term::pluck('translations', 'code_key')
                ->map(fn($t) => $t['gb'] ?? '')
                ->toArray();

            try {
                $aiService = new AiTranslationService();
                $translatedTerms = $aiService->translate($baseTerms, $locale);

                foreach ($translatedTerms as $codeKey => $translatedString) {
                    $term = Term::where('code_key', $codeKey)->first();
                    if ($term) {
                        $translations = $term->translations ?? [];
                        $translations[$locale] = $translatedString;
                        $term->update(['translations' => $translations]);
                    }
                }
            } catch (\Exception $e) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['message' => 'AI Translation Failed: ' . $e->getMessage()], 500);
                }
                return back()->with('status', 'AI Translation Failed: ' . $e->getMessage());
            }
        } else {
            Term::chunk(100, function ($terms) use ($locale) {
                foreach ($terms as $term) {
                    $translations = $term->translations ?? [];
                    if (!isset($translations[$locale])) {
                        $translations[$locale] = '';
                        $term->update(['translations' => $translations]);
                    }
                }
            });
        }

        Cache::forget('mows_lexicon');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Language module [{$locale}] initialised successfully."
            ]);
        }

        return back()->with('status', "Language module [{$locale}] initialised successfully.");
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $request->validate([
            'lang_code' => 'required|string',
            'lang_label' => 'required|string|max:255',
        ]);

        Setting::updateOrCreate(
            ['key' => 'lexicon_label_' . strtolower(trim($request->lang_code))],
            ['value' => $request->lang_label]
        );

        Cache::forget('global_settings');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => lexicon('msg_Lexicon_LangUpdated', 'Language display name updated.')
            ]);
        }

        return back()->with('status', lexicon('msg_Lexicon_LangUpdated', 'Language display name updated.'));
    }

    public function destroy($code)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $code = strtolower(trim($code));

        if ($code === 'gb') {
            return response()->json(['error' => 'Cannot delete the base language.'], 403);
        }

        Term::chunk(100, function ($terms) use ($code) {
            foreach ($terms as $term) {
                $translations = $term->translations ?? [];
                if (array_key_exists($code, $translations)) {
                    unset($translations[$code]);
                    $term->update(['translations' => $translations]);
                }
            }
        });

        Setting::where('key', 'lexicon_label_' . $code)->delete();
        Cache::forget('global_settings');
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }

    public function clear($code)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $code = strtolower(trim($code));

        if ($code === 'gb') {
            Term::truncate();
        } else {
            Term::all()->each(function ($term) use ($code) {
                $translations = $term->translations ?? [];
                unset($translations[$code]);
                if (empty($translations)) {
                    $term->delete();
                } else {
                    $term->update(['translations' => $translations]);
                }
            });
        }

        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }

    public function export($locale = 'gb')
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $locale = strtolower(trim($locale));

        $terms = Term::all()->mapWithKeys(function ($term) use ($locale) {
            $translations = $term->translations ?? [];
            $value = $translations[$locale] ?? ($translations['gb'] ?? '');
            return [$term->code_key => $value];
        })->toArray();

        $langName = ($locale === 'gb')
            ? 'English'
            : (Setting::where('key', 'lexicon_label_' . $locale)->value('value') ?? strtoupper($locale));

        $exportData = array_merge([
            '_lang' => $locale,
            '_lang_name' => $langName
        ], $terms);

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="mows_lexicon_export_' . $locale . '_' . now()->format('Y-m-d') . '.json"',
        ]);
    }

    public function import(Request $request)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        try {
            $request->validate([
                'import_file' => 'required|file|mimes:json,txt|max:10240',
                'import_lang_code' => 'nullable|string|max:10'
            ]);

            $file = $request->file('import_file');
            $fileContent = file_get_contents($file->getRealPath());
            $importedData = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'JSON Parse Error: ' . json_last_error_msg()], 422);
            }

            if (!is_array($importedData)) {
                return response()->json(['message' => 'Invalid JSON structure.'], 422);
            }

            $langCode = null;
            $langName = null;

            if (isset($importedData['_lang'])) {
                $langCode = strtolower(trim($importedData['_lang']));
                unset($importedData['_lang']);
            }
            if (isset($importedData['_lang_name'])) {
                $langName = trim($importedData['_lang_name']);
                unset($importedData['_lang_name']);
            }

            if (empty($langCode)) {
                $langCode = strtolower(trim($request->input('import_lang_code')));
            }

            if (empty($langCode)) {
                return response()->json(['message' => 'Target language could not be determined.'], 422);
            }

            $mergedCount = 0;
            $createdCount = 0;

            foreach ($importedData as $codeKey => $incomingTranslation) {
                if (empty($codeKey) || !is_string($incomingTranslation)) continue;

                if (str_starts_with($codeKey, 'public_label_')) {
                    $masterGroup = 'public_label';
                    $subgroup = substr($codeKey, 13) ?: 'general';
                } elseif (str_starts_with($codeKey, 'admin_label_')) {
                    $masterGroup = 'admin_label';
                    $subgroup = substr($codeKey, 12) ?: 'general';
                } else {
                    $parts = explode('_', $codeKey);
                    $masterGroup = count($parts) > 0 ? $parts[0] : 'general';
                    $subgroup = count($parts) > 1 ? $parts[1] : 'general';
                }

                $term = Term::where('code_key', $codeKey)->first();

                if ($term) {
                    $currentTranslations = $term->translations ?? [];
                    $currentTranslations[$langCode] = $incomingTranslation;
                    $term->translations = $currentTranslations;
                    if ($langCode === 'gb' && $incomingTranslation !== '') {
                        $term->display_value = $incomingTranslation;
                    }
                    $term->save();
                    $mergedCount++;
                } else {
                    Term::create([
                        'code_key'      => $codeKey,
                        'master_group'  => $masterGroup,
                        'subgroup'      => $subgroup,
                        'display_value' => $incomingTranslation ?: $codeKey,
                        'translations'  => [$langCode => $incomingTranslation]
                    ]);
                    $createdCount++;
                }
            }

            if ($langCode !== 'gb') {
                $settingKey = 'lexicon_label_' . $langCode;
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $langName ?: strtoupper($langCode)]
                );
                Cache::forget('global_settings');
            }

            Cache::forget('mows_lexicon');

            return response()->json([
                'success' => true,
                'message' => "Successfully merged {$mergedCount} existing terms and added {$createdCount} new terms for language [{$langCode}]."
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import Exception: ' . $e->getMessage()], 500);
        }
    }

    public function exportBlank()
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $terms = Term::all()->mapWithKeys(function ($term) {
            return [$term->code_key => ""];
        })->toArray();

        $templateData = array_merge([
            '_lang' => 'new',
            '_lang_name' => 'New Language'
        ], $terms);

        $json = json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="mows_lexicon_blank_template_' . now()->format('Y-m-d') . '.json"',
        ]);
    }
}
