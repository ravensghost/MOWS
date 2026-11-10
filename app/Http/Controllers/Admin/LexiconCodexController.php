<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\LexiconCodex;
use App\Models\Book;
use App\Models\Configuration;

class LexiconCodexController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $systemPage = Book::where('books_uri', 'admin/lexicon')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($query) {
                    $query->orderBy('module_sort_order');
                }
            ])
            ->firstOrFail();

        $locales = [];
        $terms = LexiconCodex::orderBy('prefix_group_title')
            ->orderBy('group_title')
            ->orderBy('semantic_token')
            ->get();

        $activeSkin = Configuration::where('configuration_token', 'active_skin')->value('configuration_payload') ?? 'default';
        $customLabels = Configuration::where('configuration_token', 'like', 'lexicon_label_%')->pluck('configuration_payload', 'configuration_token')->toArray();

        foreach ($terms as $term) {
            if (is_array($term->translation_payloads)) {
                foreach (array_keys($term->translation_payloads) as $langCode) {
                    if ($langCode === 'gb') {
                        $locales[$langCode] = 'English';
                    } else {
                        $settingKey = 'lexicon_label_' . $langCode;
                        $locales[$langCode] = $customLabels[$settingKey] ?? strtoupper($langCode);
                    }
                }
            }
        }

        if (!isset($locales['gb'])) {
            $locales['gb'] = 'English';
        }

        $groupedTerms = $terms->groupBy('prefix_group_title')->map(function ($groupTerms) {
            return $groupTerms->groupBy('group_title');
        });

        return view('admin.lexicon', compact('groupedTerms', 'locales', 'activeSkin', 'systemPage'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $data = $request->input('terms');

        // 1. Bulk Table Update
        if (is_array($data)) {
            foreach ($data as $id => $localeData) {
                $term = LexiconCodex::find($id);

                if ($term && is_array($localeData)) {
                    $translations = $term->translation_payloads ?? [];
                    foreach ($localeData as $locale => $value) {
                        $translations[strtolower($locale)] = $value;
                    }
                    $term->translation_payloads = $translations;
                    if (isset($translations['gb']) && $translations['gb'] !== '') {
                        $term->default_lexicon_string = $translations['gb'];
                    }
                    $term->save();
                }
            }
            Cache::forget('mows_lexicon');
        }
        // 2. Single Term Create / Edit Modal Support
        elseif ($request->filled('code_key') || $request->filled('semantic_token')) {
            $codeKey = trim($request->input('code_key') ?? $request->input('semantic_token'));
            $incomingTranslations = $request->input('translations', []);

            if (str_starts_with($codeKey, 'public_label_')) {
                $defaultMaster = 'public_label';
                $defaultSub = substr($codeKey, 13) ?: 'general';
            } elseif (str_starts_with($codeKey, 'admin_label_')) {
                $defaultMaster = 'admin_label';
                $defaultSub = substr($codeKey, 12) ?: 'general';
            } else {
                $parts = explode('_', $codeKey);
                $defaultMaster = count($parts) > 0 ? $parts[0] : 'general';
                $defaultSub = count($parts) > 1 ? $parts[1] : 'general';
            }

            $term = $request->filled('id')
                ? LexiconCodex::find($request->input('id'))
                : LexiconCodex::where('semantic_token', $codeKey)->first();

            $translations = $term ? ($term->translation_payloads ?? []) : [];
            if (is_array($incomingTranslations)) {
                foreach ($incomingTranslations as $locale => $val) {
                    $translations[strtolower($locale)] = $val ?? '';
                }
            }

            $displayValue = (!empty($translations['gb']))
                ? $translations['gb']
                : ($term->default_lexicon_string ?? $codeKey);

            if ($term) {
                $term->update([
                    'semantic_token'         => $codeKey,
                    'prefix_group_title'     => $request->input('master_group') ?: $defaultMaster,
                    'group_title'            => $request->input('subgroup') ?: $defaultSub,
                    'default_lexicon_string' => $displayValue,
                    'translation_payloads'   => $translations,
                ]);
            } else {
                LexiconCodex::create([
                    'semantic_token'         => $codeKey,
                    'prefix_group_title'     => $request->input('master_group') ?: $defaultMaster,
                    'group_title'            => $request->input('subgroup') ?: $defaultSub,
                    'default_lexicon_string' => $displayValue,
                    'translation_payloads'   => $translations,
                ]);
            }

            Cache::forget('mows_lexicon');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => lexicon('msg_Lexicon_TermUpdated', 'Lexicon term updated successfully.')
            ]);
        }

        return redirect('/admin/lexicon')->with('status', lexicon('msg_Lexicon_TermUpdated', 'Lexicon term updated successfully.'));
    }

    public function destroyTerm($id)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        LexiconCodex::findOrFail($id)->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }

    public function destroyGroup($groupName)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        LexiconCodex::where('prefix_group_title', $groupName)->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }

    public function destroySubgroup($master, $subgroup)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        LexiconCodex::where('prefix_group_title', $master)
            ->where('group_title', $subgroup)
            ->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }
}