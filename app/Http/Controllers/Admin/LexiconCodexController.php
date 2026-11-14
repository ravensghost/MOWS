<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Configuration;
use App\Models\LexiconCodex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LexiconCodexController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $systemPage = Book::where('books_uri', 'admin/lexicon-codex')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($query) {$query->orderBy('module_sort_order');
                },
            ])
            ->firstOrFail();

        $locales = [];$codexEntries = LexiconCodex::orderBy('prefix_group_title')
            ->orderBy('group_title')
            ->orderBy('semantic_token')
            ->get();

        $activeSkin = Configuration::where('configuration_token', 'active_skin')->value('configuration_payload') ?? 'default';$customLabels = Configuration::where('configuration_token', 'like', 'lexicon_label_%')
            ->pluck('configuration_payload', 'configuration_token')
            ->toArray();

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

        if (! isset($locales['gb'])) {$locales['gb'] = 'English';
        }

        $groupedTerms = $codexEntries->groupBy('prefix_group_title')->map(function ($groupEntries) {
            return $groupEntries->groupBy('group_title');
        });

        return view('admin.lexicon', compact('groupedTerms', 'locales', 'activeSkin', 'systemPage'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $data = $request->input('lexicon_entries');$model = null;

        // 1. Bulk Table Update
        if (is_array($data)) {$updatedModels = [];
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
                    $updatedModels[] =$entry;
                }
            }
            $model =$updatedModels;
            Cache::forget('lexicon_codex');
        }
        // 2. Single Entry Create / Edit Modal Support
        elseif ($request->filled('semantic_token')) {
            $semanticToken = trim($request->input('semantic_token'));
            $incomingPayloads =$request->input('translation_payloads', []);

            if (str_starts_with($semanticToken, 'public_label_')) {$defaultPrefixGroup = 'public_label';
                $remainder = substr($semanticToken, 13);
                $parts =$remainder !== '' ? explode('_', $remainder) : [];$defaultGroup = (! empty($parts[0])) ?$parts[0] : 'NotDefinedRight';
            } elseif (str_starts_with($semanticToken, 'admin_label_')) {$defaultPrefixGroup = 'admin_label';
                $remainder = substr($semanticToken, 12);
                $parts =$remainder !== '' ? explode('_', $remainder) : [];$defaultGroup = (! empty($parts[0])) ?$parts[0] : 'NotDefinedRight';
            } else {
                $parts = explode('_',$semanticToken);
                if (count($parts) >= 3 && ! empty($parts[0]) && ! empty($parts[1])) {
                    $defaultPrefixGroup =$parts[0];
                    $defaultGroup =$parts[1];
                } else {
                    $defaultPrefixGroup = ! empty($parts[0]) ? $parts[0] : 'NotDefinedRight';$defaultGroup = 'NotDefinedRight';
                }
            }

            $entry =$request->filled('id')
                ? LexiconCodex::find($request->input('id'))
                : LexiconCodex::where('semantic_token', $semanticToken)->first();

            $payloads = $entry ? ($entry->translation_payloads ?? []) : [];
            if (is_array($incomingPayloads)) {
                foreach ($incomingPayloads as$locale => $val) {$payloads[strtolower($locale)] =$val ?? '';
                }
            }

            $defaultLexiconString = (! empty($payloads['gb']))
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
                $entry = LexiconCodex::create([
                    'semantic_token'         => $semanticToken,
                    'prefix_group_title'     => $request->input('prefix_group_title') ?:$defaultPrefixGroup,
                    'group_title'            => $request->input('group_title') ?:$defaultGroup,
                    'default_lexicon_string' => $defaultLexiconString,
                    'translation_payloads'   => $payloads,
                ]);
            }

            $model =$entry;
            Cache::forget('lexicon_codex');
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lexicon_TermUpdated', 'Lexicon term updated successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function destroyTerm($id)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $model = LexiconCodex::findOrFail($id);$model->delete();
        Cache::forget('lexicon_codex');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lexicon_TermDeleted', 'Lexicon term deleted successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function destroyGroup($groupName)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $deletedCount = LexiconCodex::where('prefix_group_title',$groupName)->delete();
        Cache::forget('lexicon_codex');
        $model = ['prefix_group_title' => $groupName, 'deleted_count' =>$deletedCount];

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lexicon_GroupDeleted', 'Lexicon prefix group deleted successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    public function destroySubgroup($prefixGroupTitle,$groupTitle)
    {
        abort_unless(auth()->user()->can('manage_lexicon'), 403, lexicon('msg_Lexicon_Unauthorised', 'Unauthorised access to Lexicon Manager.'));

        $deletedCount = LexiconCodex::where('prefix_group_title',$prefixGroupTitle)
            ->where('group_title', $groupTitle)
            ->delete();
        Cache::forget('lexicon_codex');
        $model = ['prefix_group_title' =>$prefixGroupTitle, 'group_title' => $groupTitle, 'deleted_count' =>$deletedCount];

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lexicon_SubgroupDeleted', 'Lexicon subgroup deleted successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }
}