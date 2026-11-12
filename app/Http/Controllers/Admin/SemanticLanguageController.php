<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\AiTranslationService;
use App\Models\LexiconCodex;
use App\Models\Configuration;

class SemanticLanguageController extends Controller
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

        if ($request->lang_type === 'custom' &&$request->filled('custom_lang_name')) {
            Configuration::updateOrCreate(
                ['configuration_token' => 'lexicon_label_' . $locale],
                ['configuration_payload' => trim($request->custom_lang_name)]
            );
            Cache::forget('global_settings');
        }

        if ($request->lang_type === 'common') {$baseEntries = LexiconCodex::pluck('translation_payloads', 'semantic_token')
                ->map(fn($t) =>$t['gb'] ?? '')
                ->toArray();

            try {
                $aiService = new AiTranslationService();
                $translatedEntries =$aiService->translate($baseEntries,$locale);

                foreach ($translatedEntries as $semanticToken =>$translatedString) {
                    $entry = LexiconCodex::where('semantic_token',$semanticToken)->first();
                    if ($entry) {
                        $payloads =$entry->translation_payloads ?? [];
                        $payloads[$locale] =$translatedString;
                        $entry->update(['translation_payloads' =>$payloads]);
                    }
                }
            } catch (\Exception $e) {
                if ($request->wantsJson() ||$request->ajax()) {
                    return response()->json(['message' => 'AI Translation Failed: ' . $e->getMessage()], 500);
                }
                return back()->with('status', 'AI Translation Failed: ' . $e->getMessage());
            }
        } else {
            LexiconCodex::chunk(100, function ($entries) use ($locale) {
                foreach ($entries as$entry) {
                    $payloads =$entry->translation_payloads ?? [];
                    if (!isset($payloads[$locale])) {
                        $payloads[$locale] = '';
                        $entry->update(['translation_payloads' =>$payloads]);
                    }
                }
            });
        }

        Cache::forget('mows_lexicon');

        if ($request->wantsJson() ||$request->ajax()) {
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

        Configuration::updateOrCreate(
            ['configuration_token' => 'lexicon_label_' . strtolower(trim($request->lang_code))],
            ['configuration_payload' => $request->lang_label]
        );

        Cache::forget('global_settings');

        if ($request->wantsJson() ||$request->ajax()) {
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
            return response()->json([
                'status'  => 'error',
                'message' => lexicon('msg_Lexicon_CannotDeleteCore', 'The core British English language pack cannot be deleted.')
            ], 422);
        }

        $globalSetting = Configuration::where('configuration_token', 'global_lexicon_setting')->first();
        if ($globalSetting &&$globalSetting->configuration_payload === $code) {$globalSetting->update(['configuration_payload' => 'auto']);
        }

        LexiconCodex::chunk(100, function ($entries) use ($code) {
            foreach ($entries as$entry) {
                $payloads =$entry->translation_payloads ?? [];
                if (array_key_exists($code,$payloads)) {
                    unset($payloads[$code]);
                    $entry->update(['translation_payloads' =>$payloads]);
                }
            }
        });

        Configuration::where('configuration_token', 'lexicon_label_' . $code)->delete();
        Cache::forget('global_settings');
        Cache::forget('mows_lexicon');

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Lexicon_LanguageDeleted', 'Language pack deleted successfully and global settings updated.')
        ]);
    }

    public function clear($code)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $code = strtolower(trim($code));

        if ($code === 'gb') {
            LexiconCodex::truncate();
        } else {
            LexiconCodex::all()->each(function ($entry) use ($code) {
                $payloads =$entry->translation_payloads ?? [];
                unset($payloads[$code]);
                if (empty($payloads)) {$entry->delete();
                } else {
                    $entry->update(['translation_payloads' =>$payloads]);
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

        $entries = LexiconCodex::all()->mapWithKeys(function ($entry) use ($locale) {
            $payloads =$entry->translation_payloads ?? [];
            $value =$payloads[$locale] ?? ($payloads['gb'] ?? '');
            return [$entry->semantic_token =>$value];
        })->toArray();

        $langName = ($locale === 'gb')
            ? 'English'
            : (Configuration::where('configuration_token', 'lexicon_label_' . $locale)->value('configuration_payload') ?? strtoupper($locale));

        $exportData = array_merge([
            '_lang' => $locale,
            '_lang_name' => $langName
        ], $entries);

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

            $file =$request->file('import_file');
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

            foreach ($importedData as $semanticToken =>$incomingTranslation) {
                if (empty($semanticToken) || !is_string($incomingTranslation)) continue;

                if (str_starts_with($semanticToken, 'public_label_')) {$prefixGroupTitle = 'public_label';
                    $groupTitle = substr($semanticToken, 13) ?: 'general';
                } elseif (str_starts_with($semanticToken, 'admin_label_')) {$prefixGroupTitle = 'admin_label';
                    $groupTitle = substr($semanticToken, 12) ?: 'general';
                } else {
                    $parts = explode('_',$semanticToken);
                    $prefixGroupTitle = count($parts) > 0 ? $parts[0] : 'general';$groupTitle = count($parts) > 1 ?$parts[1] : 'general';
                }

                $entry = LexiconCodex::where('semantic_token',$semanticToken)->first();

                if ($entry) {
                    $currentPayloads =$entry->translation_payloads ?? [];
                    $currentPayloads[$langCode] =$incomingTranslation;
                    $entry->translation_payloads =$currentPayloads;
                    if ($langCode === 'gb' &&$incomingTranslation !== '') {
                        $entry->default_lexicon_string =$incomingTranslation;
                    }
                    $entry->save();$mergedCount++;
                } else {
                    LexiconCodex::create([
                        'semantic_token'         => $semanticToken,
                        'prefix_group_title'     => $prefixGroupTitle,
                        'group_title'            => $groupTitle,
                        'default_lexicon_string' => $incomingTranslation ?: $semanticToken,
                        'translation_payloads'   => [$langCode =>$incomingTranslation]
                    ]);
                    $createdCount++;
                }
            }

            if ($langCode !== 'gb') {
                $settingKey = 'lexicon_label_' .$langCode;
                Configuration::updateOrCreate(
                    ['configuration_token' => $settingKey],
                    ['configuration_payload' => $langName ?: strtoupper($langCode)]
                );
                Cache::forget('global_settings');
            }

            Cache::forget('mows_lexicon');

            return response()->json([
                'success' => true,
                'message' => "Successfully merged {$mergedCount} existing entries and added {$createdCount} new entries for language [{$langCode}]."
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import Exception: ' . $e->getMessage()], 500);
        }
    }

    public function exportBlank()
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $entries = LexiconCodex::all()->mapWithKeys(function ($entry) {
            return [$entry->semantic_token => ""];
        })->toArray();

        $templateData = array_merge([ '_lang' => 'new', '_lang_name' => 'New Language' ],$entries);

        $json = json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="mows_lexicon_blank_template_' . now()->format('Y-m-d') . '.json"',
        ]);
    }
}