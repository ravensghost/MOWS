<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Term;
use App\Models\Page;
use App\Models\Setting;

class LexiconTermController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $systemPage = Page::where('slug', 'admin/lexicon')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $locales = [];
        $terms = Term::orderBy('master_group')->orderBy('subgroup')->orderBy('code_key')->get();

        $activeSkin = Setting::where('key', 'active_skin')->value('value') ?? 'default';
        $customLabels = Setting::where('key', 'like', 'lexicon_label_%')->pluck('value', 'key')->toArray();

        foreach ($terms as $term) {
            if (is_array($term->translations)) {
                foreach (array_keys($term->translations) as $langCode) {
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

        $groupedTerms = $terms->groupBy('master_group')->map(function ($groupTerms) {
            return $groupTerms->groupBy('subgroup');
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
                $term = Term::find($id);

                if ($term && is_array($localeData)) {
                    $translations = $term->translations ?? [];
                    foreach ($localeData as $locale => $value) {
                        $translations[strtolower($locale)] = $value;
                    }
                    $term->translations = $translations;
                    if (isset($translations['gb']) && $translations['gb'] !== '') {
                        $term->display_value = $translations['gb'];
                    }
                    $term->save();
                }
            }
            Cache::forget('mows_lexicon');
        }
        // 2. Single Term Create / Edit Modal Support
        elseif ($request->filled('code_key')) {
            $codeKey = trim($request->input('code_key'));
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
                ? Term::find($request->input('id'))
                : Term::where('code_key', $codeKey)->first();

            $translations = $term ? ($term->translations ?? []) : [];
            if (is_array($incomingTranslations)) {
                foreach ($incomingTranslations as $locale => $val) {
                    $translations[strtolower($locale)] = $val ?? '';
                }
            }

            $displayValue = (!empty($translations['gb']))
                ? $translations['gb']
                : ($term->display_value ?? $codeKey);

            if ($term) {
                $term->update([
                    'code_key'      => $codeKey,
                    'master_group'  => $request->input('master_group') ?: $defaultMaster,
                    'subgroup'      => $request->input('subgroup') ?: $defaultSub,
                    'display_value' => $displayValue,
                    'translations'  => $translations,
                ]);
            } else {
                Term::create([
                    'code_key'      => $codeKey,
                    'master_group'  => $request->input('master_group') ?: $defaultMaster,
                    'subgroup'      => $request->input('subgroup') ?: $defaultSub,
                    'display_value' => $displayValue,
                    'translations'  => $translations,
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

        Term::findOrFail($id)->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }

    public function destroyGroup($groupName)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        Term::where('master_group', $groupName)->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }

    public function destroySubgroup($master, $subgroup)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        Term::where('master_group', $master)
            ->where('subgroup', $subgroup)
            ->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }
}
