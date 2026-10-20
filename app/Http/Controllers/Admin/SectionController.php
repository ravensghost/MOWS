<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Section;
use App\Models\Page;

class SectionController extends Controller
{
    /**
     * Clear cached section colors and roster manifest data.
     */
    private function clearSectionCaches(): void
    {
        Cache::forget('global_section_colors');
        Cache::forget('widget_roster_sections');
        Cache::forget('widget_manifest_sections');
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $systemPage = Page::where('slug', 'admin/sections')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $sections = Section::with([
            'rankSet',
            'positions.characters' => function ($query) {
                $query->where('status', 'active');
            }
        ])->orderBy('order')->get();

        return view('admin.sections', compact('sections', 'systemPage'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'color'       => 'nullable|string|max:9',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer',
            'is_visible'  => 'nullable|boolean',
        ]);

        $section = Section::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? '#374151',
            'order'       => $validated['order'] ?? 0,
            'is_visible'  => $request->boolean('is_visible', false),
        ]);

        $this->clearSectionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $section,
            'message' => lexicon('msg_SectionsPositions_SectionCreated', 'Section created successfully.'),
        ]);
    }

    public function update(Request $request, Section $section)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'color'       => 'required|string|max:9',
            'description' => 'nullable|string',
            'order'       => 'required|integer',
            'is_visible'  => 'nullable|boolean',
        ]);

        $section->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'],
            'order'       => $validated['order'],
            'is_visible'  => $request->boolean('is_visible', false),
        ]);

        $this->clearSectionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $section,
            'message' => lexicon('msg_SectionsPositions_SectionUpdated', 'Section updated successfully.'),
        ]);
    }

    public function destroy(Section $section)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $section->delete();

        $this->clearSectionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_SectionDeleted', 'Section deleted successfully.'),
        ]);
    }

    public function generateDescription(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $name = trim((string) $request->input('name'));

        if (empty($name)) {
            return response()->json([
                'error' => lexicon('msg_SectionsPositions_EnterNameFirst', 'Please enter a Section Name first.')
            ], 422);
        }

        $mockAiResponse = "The {$name} division is responsible for maintaining the operational integrity and strategic objectives of their assigned sector, ensuring all protocols are strictly adhered to under changing conditions.";

        return response()->json([
            'status'      => 'success',
            'description' => $mockAiResponse,
        ]);
    }
    public function export()
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $sections = Section::with(['rankSet', 'positions'])->orderBy('order')->get()->map(function ($section) {
            return [
                'name'         => $section->name,
                'description'  => $section->description,
                'color'        => $section->color,
                'order'        => $section->order,
                'is_visible'   => (bool) $section->is_visible,
                'rank_set_name' => $section->rankSet?->name,
                'positions'    => $section->positions->map(function ($pos) {
                    return [
                        'name'        => $pos->name,
                        'description' => $pos->description,
                        'capacity'    => $pos->capacity,
                        'is_open'     => (bool) $pos->is_open,
                        'is_visible'  => (bool) $pos->is_visible,
                    ];
                })->all(),
            ];
        });

        $manifest = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'sections_positions_manifest',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'sections' => $sections->all(),
        ];

        return response()->streamDownload(function () use ($manifest) {
            echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_sections_positions_export.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function template()
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $template = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'sections_positions_manifest',
                'version'     => '1.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'sections' => [
                [
                    'name'         => 'Command',
                    'description'  => 'Station command and executive management.',
                    'color'        => '#dc2626',
                    'order'        => 1,
                    'is_visible'   => true,
                    'rank_set_name' => 'writers',
                    'positions'    => [
                        [
                            'name'        => 'Commanding Officer',
                            'description' => 'Overall station command.',
                            'capacity'    => 1,
                            'is_open'     => true,
                            'is_visible'  => true,
                        ]
                    ]
                ]
            ],
        ];

        return response()->streamDownload(function () use ($template) {
            echo json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_sections_positions_template.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $request->validate([
            'file' => 'required|file|mimes:json,txt|max:2048',
            'mode' => 'required|in:merge,replace',
        ]);

        $path = $request->file('file')->getRealPath();
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!isset($data['sections']) || !is_array($data['sections'])) {
            return response()->json([
                'status'  => 'error',
                'message' => lexicon('msg_SectionsPositions_InvalidManifest', 'Invalid sections manifest format.'),
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            if ($request->input('mode') === 'replace') {
                Section::query()->delete(); // Cascades or clears positions cleanly
            }

            foreach ($data['sections'] as $secData) {
                $rankSetId = null;
                if (!empty($secData['rank_set_name'])) {
                    $rankSet = \App\Models\RankSet::where('name', $secData['rank_set_name'])->first();
                    $rankSetId = $rankSet?->id;
                }

                $section = Section::updateOrCreate(
                    ['name' => $secData['name']],
                    [
                        'description' => $secData['description'] ?? null,
                        'color'       => $secData['color'] ?? '#374151',
                        'order'       => $secData['order'] ?? 0,
                        'is_visible'  => $secData['is_visible'] ?? true,
                        'rank_set_id' => $rankSetId,
                    ]
                );

                if (!empty($secData['positions']) && is_array($secData['positions'])) {
                    foreach ($secData['positions'] as $posData) {
                        Position::updateOrCreate(
                            [
                                'section_id' => $section->id,
                                'name'       => $posData['name']
                            ],
                            [
                                'description' => $posData['description'] ?? null,
                                'capacity'    => $posData['capacity'] ?? 1,
                                'is_open'     => $posData['is_open'] ?? true,
                                'is_visible'  => $posData['is_visible'] ?? true,
                            ]
                        );
                    }
                }
            }
        });

        $this->clearSectionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_ImportSuccess', 'Sections and positions imported successfully.'),
        ]);
    }
}
