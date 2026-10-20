<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Models\RankSet;
use App\Models\Section;
use App\Models\Skin;
use App\Models\Setting;
use App\Models\Page;
use App\Models\Ranks;

class RankController extends Controller
{
    /**
     * Clear cached roster rank data.
     */
    private function clearRankCaches(): void
    {
        Cache::forget('widget_roster_sections');
    }

    public function index()
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $systemPage = Page::where('slug', 'admin/ranks')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $rankSets = RankSet::with(['ranks' => function ($q) {
            $q->orderBy('sort_order');
        }, 'sections'])->get();

        $sections = Section::orderBy('order')->get();

        $skinSettings = Setting::where('key', 'like', 'theme_%')->pluck('value', 'key')->toArray();
        $baseLayer = $skinSettings['theme_base'] ?? '#1a1a1a';
        $departmentColor = '#991b1b';

        $midShapes = [];
        $topShapes = [];
        $cssPath = resource_path('css/ranks.css');

        if (File::exists($cssPath)) {
            $cssContent = File::get($cssPath);

            preg_match_all('/\.shape-mid-([a-zA-Z0-9\-]+)/', $cssContent, $midMatches);
            preg_match_all('/\.shape-top-([a-zA-Z0-9\-]+)/', $cssContent, $topMatches);

            if (!empty($midMatches[0])) {
                foreach (array_unique($midMatches[0]) as $class) {
                    $className = ltrim($class, '.');
                    $parts = explode('-', $className);
                    $label = ucfirst(end($parts));
                    $midShapes[$className] = $label;
                }
            }

            if (!empty($topMatches[0])) {
                foreach (array_unique($topMatches[0]) as $class) {
                    $className = ltrim($class, '.');
                    $parts = explode('-', $className);
                    $label = ucfirst(end($parts));
                    $topShapes[$className] = $label;
                }
            }
        }

        return view('admin.ranks', compact('rankSets', 'sections', 'baseLayer', 'departmentColor', 'midShapes', 'topShapes', 'systemPage'));
    }

    public function images(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rankSet = basename($request->query('set_name', ''));

        $directory = public_path("images/ranks/{$rankSet}");
        $images = [];

        if (File::exists($directory)) {
            $files = File::files($directory);
            foreach ($files as $file) {
                if (in_array(strtolower($file->getExtension()), ['png', 'svg', 'webp', 'jpg', 'jpeg'])) {
                    $images[] = $file->getFilename();
                }
            }
        }

        return response()->json(['status' => 'success', 'images' => $images]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated = $request->validate([
            'rank_set_id' => 'required|exists:rank_sets,id',
            'name'        => 'required|string|max:255',
            'short_name'  => 'required|string|max:50',
            'mid_type'    => 'required|in:css,image',
            'mid_image'   => 'nullable|string',
            'mid_css'     => 'nullable|string',
            'mid_color'   => 'nullable|string|max:50',
            'top_type'    => 'required|in:css,image',
            'top_image'   => 'nullable|string',
            'top_css'     => 'nullable|string',
            'top_color'   => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer',
            'is_visible'  => 'nullable|boolean',
        ]);

        $validated['is_visible'] = $request->boolean('is_visible', false);
        Ranks::create($validated);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Created', 'Rank created successfully.')]);
    }

    public function update(Request $request, Ranks $rank)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated = $request->validate([
            'rank_set_id' => 'required|exists:rank_sets,id',
            'name'        => 'required|string|max:255',
            'short_name'  => 'required|string|max:50',
            'mid_type'    => 'required|in:css,image',
            'mid_image'   => 'nullable|string',
            'mid_css'     => 'nullable|string',
            'mid_color'   => 'nullable|string|max:50',
            'top_type'    => 'required|in:css,image',
            'top_image'   => 'nullable|string',
            'top_css'     => 'nullable|string',
            'top_color'   => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order'  => 'nullable|integer',
            'is_visible'  => 'nullable|boolean',
        ]);

        $validated['is_visible'] = $request->boolean('is_visible', false);
        $rank->update($validated);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Updated', 'Rank updated successfully.')]);
    }

    public function destroy(Ranks $rank)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rank->delete();

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Deleted', 'Rank deleted successfully.')]);
    }

    public function reorder(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated = $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:ranks,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            Ranks::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Reordered', 'Ranks reordered successfully.')]);
    }
    public function export()
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rankSets = RankSet::with('ranks')->get()->map(function ($set) {
            return [
                'name'       => $set->name,
                'is_visible' => (bool) $set->is_visible,
                'ranks'      => $set->ranks->map(function ($rank) {
                    return [
                        'name'        => $rank->name,
                        'short_name'  => $rank->short_name,
                        'description' => $rank->description,
                        'sort_order'  => $rank->sort_order,
                        'is_visible'  => (bool) $rank->is_visible,
                        'mid_type'    => $rank->mid_type,
                        'mid_image'   => $rank->mid_image,
                        'mid_css'     => $rank->mid_css,
                        'mid_color'   => $rank->mid_color,
                        'top_type'    => $rank->top_type,
                        'top_image'   => $rank->top_image,
                        'top_css'     => $rank->top_css,
                        'top_color'   => $rank->top_color,
                    ];
                })->all(),
            ];
        });

        $manifest = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'ranks_manifest',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'rank_sets' => $rankSets->all(),
        ];

        return response()->streamDownload(function () use ($manifest) {
            echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_ranks_export.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function template()
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $template = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'ranks_manifest',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'rank_sets' => [
                [
                    'name'       => 'Starfleet_2409',
                    'is_visible' => true,
                    'ranks'      => [
                        [
                            'name'        => 'Captain',
                            'short_name'  => 'CAPT',
                            'description' => 'Commanding officer rank.',
                            'sort_order'  => 1,
                            'is_visible'  => true,
                            'mid_type'    => 'css',
                            'mid_image'   => null,
                            'mid_css'     => 'shape-mid-original',
                            'mid_color'   => '#1a1a1aFF',
                            'top_type'    => 'image',
                            'top_image'   => 'sgp.png',
                            'top_css'     => null,
                            'top_color'   => '#ffffffFF',
                        ]
                    ]
                ]
            ],
        ];

        return response()->streamDownload(function () use ($template) {
            echo json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_ranks_template.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $request->validate([
            'file' => 'required|file|mimes:json,txt|max:2048',
            'mode' => 'required|in:merge,replace',
        ]);

        $path = $request->file('file')->getRealPath();
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!isset($data['rank_sets']) || !is_array($data['rank_sets'])) {
            return response()->json([
                'status'  => 'error',
                'message' => lexicon('msg_Ranks_InvalidManifest', 'Invalid ranks manifest format.'),
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            if ($request->input('mode') === 'replace') {
                RankSet::query()->delete(); // Cascades and deletes ranks cleanly
            }

            foreach ($data['rank_sets'] as $setData) {
                $rankSet = RankSet::updateOrCreate(
                    ['name' => $setData['name']],
                    ['is_visible' => $setData['is_visible'] ?? true]
                );

                if (!empty($setData['ranks']) && is_array($setData['ranks'])) {
                    foreach ($setData['ranks'] as $rankData) {
                        Ranks::updateOrCreate(
                            [
                                'rank_set_id' => $rankSet->id,
                                'name'        => $rankData['name']
                            ],
                            [
                                'short_name'  => $rankData['short_name'] ?? '',
                                'description' => $rankData['description'] ?? null,
                                'sort_order'  => $rankData['sort_order'] ?? 0,
                                'is_visible'  => $rankData['is_visible'] ?? true,
                                'mid_type'    => $rankData['mid_type'] ?? 'css',
                                'mid_image'   => $rankData['mid_image'] ?? null,
                                'mid_css'     => $rankData['mid_css'] ?? null,
                                'mid_color'   => $rankData['mid_color'] ?? null,
                                'top_type'    => $rankData['top_type'] ?? 'image',
                                'top_image'   => $rankData['top_image'] ?? null,
                                'top_css'     => $rankData['top_css'] ?? null,
                                'top_color'   => $rankData['top_color'] ?? null,
                            ]
                        );
                    }
                }
            }
        });

        $this->clearRankCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Ranks_ImportSuccess', 'Ranks imported successfully.'),
        ]);
    }
}
