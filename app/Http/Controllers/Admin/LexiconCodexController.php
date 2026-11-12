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
                'module' => function ($query) {$query->orderBy('module_sort_order');
                }
            ])
            ->firstOrFail();

        $locales = [];$codexEntries = LexiconCodex::orderBy('prefix_group_title')
            ->orderBy('group_title')
            ->orderBy('semantic_token')
            ->get();

        $activeSkin = Configuration::where('configuration_token', 'active_skin')->value('configuration_payload') ?? 'default';$customLabels = Configuration::where('configuration_token', 'like', 'lexicon_label_%')->pluck('configuration_payload', 'configuration_token')->toArray();

        foreach ($codexEntries as$entry) {
            if (is_array($entry->translation_payloads)) {
                foreach (array_keys($entry->translation_payloads) as$langCode) {
                    if ($langCode === 'gb') {
                        $locales[$langCode] = 'English';
                    } else {
                        $settingKey = 'lexicon_label_' .$langCode;
                        $locales[$langCode] =$customLabels[$settingKey] ?? strtoupper($langCode);
                    }
                }
            }
        }

        if (!isset($locales['gb'])) {$locales['gb'] = 'English';
        }

        $groupedTerms = $codexEntries->groupBy('prefix_group_title')->map(function ($groupEntries) {
            return $groupEntries->groupBy('group_title');
        });

        return view('admin.lexicon', compact('groupedTerms', 'locales', 'activeSkin', 'systemPage'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $data =$request->input('lexicon_entries');

        // 1. Bulk Table Update
        if (is_array($data)) {
            foreach ($data as $id =>$localeData) {
                $entry = LexiconCodex::find($id);

                if ($entry && is_array($localeData)) {
                    $payloads =$entry->translation_payloads ?? [];
                    foreach ($localeData as$locale => $value) {$payloads[strtolower($locale)] =$value;
                    }
                    $entry->translation_payloads =$payloads;
                    if (isset($payloads['gb']) &&$payloads['gb'] !== '') {
                        $entry->default_lexicon_string =$payloads['gb'];
                    }
                    $entry->save();
                }
            }
            Cache::forget('mows_lexicon');
        }
        // 2. Single Entry Create / Edit Modal Support
        elseif ($request->filled('semantic_token')) {
            $semanticToken = trim($request->input('semantic_token'));
            $incomingPayloads =$request->input('translation_payloads', []);

            if (str_starts_with($semanticToken, 'public_label_')) {$defaultPrefixGroup = 'public_label';
                $defaultGroup = substr($semanticToken, 13) ?: 'general';
            } elseif (str_starts_with($semanticToken, 'admin_label_')) {$defaultPrefixGroup = 'admin_label';
                $defaultGroup = substr($semanticToken, 12) ?: 'general';
            } else {
                $parts = explode('_',$semanticToken);
                $defaultPrefixGroup = count($parts) > 0 ? $parts[0] : 'general';$defaultGroup = count($parts) > 1 ?$parts[1] : 'general';
            }

            $entry =$request->filled('id')
                ? LexiconCodex::find($request->input('id'))
                : LexiconCodex::where('semantic_token', $semanticToken)->first();

            $payloads = $entry ? ($entry->translation_payloads ?? []) : [];
            if (is_array($incomingPayloads)) {
                foreach ($incomingPayloads as$locale => $val) {$payloads[strtolower($locale)] =$val ?? '';
                }
            }

            $defaultLexiconString = (!empty($payloads['gb']))
                ? $payloads['gb']
                : ($entry->default_lexicon_string ?? $semanticToken);

            if ($entry) {$entry->update([
                    'semantic_token'         => $semanticToken,
                    'prefix_group_title'     => $request->input('prefix_group_title') ?:$defaultPrefixGroup,
                    'group_title'            => $request->input('group_title') ?:$defaultGroup,
                    'default_lexicon_string' => $defaultLexiconString,
                    'translation_payloads'   => $payloads,
                ]);
            } else {
                LexiconCodex::create([
                    'semantic_token'         => $semanticToken,
                    'prefix_group_title'     => $request->input('prefix_group_title') ?:$defaultPrefixGroup,
                    'group_title'            => $request->input('group_title') ?:$defaultGroup,
                    'default_lexicon_string' => $defaultLexiconString,
                    'translation_payloads'   => $payloads,
                ]);
            }

            Cache::forget('mows_lexicon');
        }

        if ($request->wantsJson() ||$request->ajax()) {
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

    public function destroySubgroup($prefixGroupTitle,$groupTitle)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        LexiconCodex::where('prefix_group_title', $prefixGroupTitle)
            ->where('group_title', $groupTitle)
            ->delete();
        Cache::forget('mows_lexicon');

        return response()->json(['success' => true]);
    }
}