<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\InsigniaCatalogue;
use App\Models\Division;
use App\Models\Configuration;
use App\Models\Page;
use App\Models\Insignia;

class InsigniaController extends Controller
{
    /**
     * Clear cached roster insignia data.
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
            ->with(['layout.blocks', 'widgets' => function ($query) {$query->orderBy('page_widget.insignia_sort_order');
            }])
            ->firstOrFail();

        $rankSets = InsigniaCatalogue::with(['insignia' => function ($q) {$q->orderBy('insignia_sort_order');
        }, 'divisions'])->get();

        $sections = Division::orderBy('division_sort_order')->get();

        $skinSettings = Configuration::where('configuration_token', 'like', 'theme_%')->pluck('configuration_payload', 'configuration_token')->toArray();
        $baseLayer =$skinSettings['theme_base'] ?? '#1a1a1a';
        $departmentColor = '#991b1b';

        $midShapes = [];
        $topShapes = [];$cssPath = resource_path('css/ranks.css');

        if (File::exists($cssPath)) {
            $cssContent = File::get($cssPath);

            preg_match_all('/\.shape-mid-([a-zA-Z0-9\-]+)/', $cssContent,$midMatches);
            preg_match_all('/\.shape-top-([a-zA-Z0-9\-]+)/', $cssContent,$topMatches);

            if (!empty($midMatches[0])) {
                foreach (array_unique($midMatches[0]) as $class) {$className = ltrim($class, '.');$parts = explode('-', $className);$label = ucfirst(end($parts));$midShapes[$className] =$label;
                }
            }

            if (!empty($topMatches[0])) {
                foreach (array_unique($topMatches[0]) as $class) {$className = ltrim($class, '.');$parts = explode('-', $className);$label = ucfirst(end($parts));$topShapes[$className] =$label;
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
            foreach ($files as$file) {
                if (in_array(strtolower($file->getExtension()), ['png', 'svg', 'webp', 'jpg', 'jpeg'])) {
                    $images[] =$file->getFilename();
                }
            }
        }

        return response()->json(['status' => 'success', 'images' => $images]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated =$request->validate([
            'insignia_catalogue_id' => 'required|exists:insignia_catalogues,id',
            'insignia_title'        => 'required|string|max:255',
            'abbreviated_title'     => 'required|string|max:50',
            'mid_layer_mode'        => 'required|in:css,image',
            'mid_layer_asset'       => 'nullable|string',
            'mid_layer_modifier'    => 'nullable|string',
            'mid_layer_hue'         => 'nullable|string|max:50',
            'top_layer_mode'        => 'required|in:css,image',
            'top_layer_asset'       => 'nullable|string',
            'top_layer_modifier'    => 'nullable|string',
            'top_layer_hue'         => 'nullable|string|max:50',
            'insignia_description'  => 'nullable|string',
            'insignia_sort_order'   => 'nullable|integer',
            'is_visible'            => 'nullable|boolean',
        ]);

        $validated['is_visible'] =$request->boolean('is_visible', false);
        Insignia::create($validated);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Created', 'Rank created successfully.')]);
    }

    public function update(Request $request, Insignia$rank)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated =$request->validate([
            'insignia_catalogue_id' => 'required|exists:insignia_catalogues,id',
            'insignia_title'        => 'required|string|max:255',
            'abbreviated_title'     => 'required|string|max:50',
            'mid_layer_mode'        => 'required|in:css,image',
            'mid_layer_asset'       => 'nullable|string',
            'mid_layer_modifier'    => 'nullable|string',
            'mid_layer_hue'         => 'nullable|string|max:50',
            'top_layer_mode'        => 'required|in:css,image',
            'top_layer_asset'       => 'nullable|string',
            'top_layer_modifier'    => 'nullable|string',
            'top_layer_hue'         => 'nullable|string|max:50',
            'insignia_description'  => 'nullable|string',
            'insignia_sort_order'   => 'nullable|integer',
            'is_visible'            => 'nullable|boolean',
        ]);

        $validated['is_visible'] =$request->boolean('is_visible', false);
        $rank->update($validated);

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Updated', 'Rank updated successfully.')]);
    }

    public function destroy(Insignia $rank)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rank->delete();

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Deleted', 'Rank deleted successfully.')]);
    }

    public function reorder(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated =$request->validate([
            'items'                       => 'required|array',
            'items.*.id'                  => 'required|exists:insignia,id',
            'items.*.insignia_sort_order' => 'required|integer',
        ]);

        foreach ($validated['items'] as$item) {
            Insignia::where('id', $item['id'])->update(['insignia_sort_order' =>$item['insignia_sort_order']]);
        }

        $this->clearRankCaches();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Reordered', 'Ranks reordered successfully.')]);
    }

    public function export()
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rankSets = InsigniaCatalogue::with('insignia')->get()->map(function ($set) {
            return [
                'catalogue_title' => $set->catalogue_title,
                'is_visible'      => (bool) $set->is_visible,
                'insignia'        => $set->insignia->map(function ($rank) {
                    return [
                        'insignia_title'       => $rank->insignia_title,
                        'abbreviated_title'    => $rank->abbreviated_title,
                        'insignia_description' => $rank->insignia_description,
                        'insignia_sort_order'  => $rank->insignia_sort_order,
                        'is_visible'           => (bool) $rank->is_visible,
                        'mid_layer_mode'       => $rank->mid_layer_mode,
                        'mid_layer_asset'      => $rank->mid_layer_asset,
                        'mid_layer_modifier'   => $rank->mid_layer_modifier,
                        'mid_layer_hue'        => $rank->mid_layer_hue,
                        'top_layer_mode'       => $rank->top_layer_mode,
                        'top_layer_asset'      => $rank->top_layer_asset,
                        'top_layer_modifier'   => $rank->top_layer_modifier,
                        'top_layer_hue'        => $rank->top_layer_hue,
                    ];
                })->all(),
            ];
        });

        $manifest = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'insignia_manifest',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'insignia_catalogues' => $rankSets->all(),
        ];

        return response()->streamDownload(function () use ($manifest) {
            echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_insignia_export.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function template()
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $template = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'insignia_manifest',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'insignia_catalogues' => [
                [
                    'catalogue_title' => 'Starfleet_2409',
                    'is_visible'      => true,
                    'insignia'        => [
                        [
                            'insignia_title'       => 'Captain',
                            'abbreviated_title'    => 'CAPT',
                            'insignia_description' => 'Commanding officer rank.',
                            'insignia_sort_order'  => 1,
                            'is_visible'           => true,
                            'mid_layer_mode'       => 'css',
                            'mid_layer_asset'      => null,
                            'mid_layer_modifier'   => 'shape-mid-original',
                            'mid_layer_hue'        => '#1a1a1aFF',
                            'top_layer_mode'       => 'image',
                            'top_layer_asset'      => 'sgp.png',
                            'top_layer_modifier'   => null,
                            'top_layer_hue'        => '#ffffffFF',
                        ]
                    ]
                ]
            ],
        ];

        return response()->streamDownload(function () use ($template) {
            echo json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_insignia_template.json', [
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

        $path =$request->file('file')->getRealPath();
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!isset($data['insignia_catalogues']) \vert{}\vert{} !is_array($data['insignia_catalogues'])) {
            return response()->json([
                'status'  => 'error',
                'message' => lexicon('msg_Ranks_InvalidManifest', 'Invalid insignia manifest format.'),
            ], 422);
        }

        DB::transaction(function () use ($data,$request) {
            if ($request->input('mode') === 'replace') {
                InsigniaCatalogue::query()->delete();
            }

            foreach ($data['insignia_catalogues'] as $setData) {$rankSet = InsigniaCatalogue::updateOrCreate(
                    ['catalogue_title' => $setData['catalogue_title']],
                    ['is_visible' => $setData['is_visible'] ?? true]
                );

                if (!empty($setData['insignia']) && is_array($setData['insignia'])) {
                    foreach ($setData['insignia'] as$rankData) {
                        Insignia::updateOrCreate(
                            [
                                'insignia_catalogue_id' => $rankSet->id,
                                'insignia_title'        => $rankData['insignia_title']
                            ],
                            [
                                'abbreviated_title'    => $rankData['abbreviated_title'] ?? '',
                                'insignia_description' => $rankData['insignia_description'] ?? null,
                                'insignia_sort_order'  => $rankData['insignia_sort_order'] ?? 0, 'is_visible'           =>$rankData['is_visible'] ?? true,
                                'mid_layer_mode'       => $rankData['mid_layer_mode'] ?? 'css',
                                'mid_layer_asset'      => $rankData['mid_layer_asset'] ?? null,
                                'mid_layer_modifier'   => $rankData['mid_layer_modifier'] ?? null,
                                'mid_layer_hue'        => $rankData['mid_layer_hue'] ?? null,
                                'top_layer_mode'       => $rankData['top_layer_mode'] ?? 'image',
                                'top_layer_asset'      => $rankData['top_layer_asset'] ?? null,
                                'top_layer_modifier'   => $rankData['top_layer_modifier'] ?? null,
                                'top_layer_hue'        => $rankData['top_layer_hue'] ?? null,
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