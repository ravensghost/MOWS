<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Division;
use App\Models\DivisionCatalogue;
use App\Models\InsigniaCatalogue;
use App\Models\Assignment;
use App\Models\Page;
use App\Services\AILoreService;

class DivisionController extends Controller
{
    /**
     * Clear cached division base colours and roster manifest data.
     */
    private function clearDivisionCaches(): void
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

        $sections = Division::with([
            'insigniaCatalogue',
            'divisionCatalogue',
            'assignments.characters' => function ($query) {
                $query->where('status', 'active');
            }
        ])->orderBy('division_sort_order')->get();

        $catalogues = DivisionCatalogue::with('divisions')->orderBy('catalogue_title')->get();

        return view('admin.sections', compact('sections', 'catalogues', 'systemPage'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'division_title'        => 'required|string|max:255',
            'division_base_colour'  => 'nullable|string|max:9',
            'division_description'  => 'nullable|string',
            'division_sort_order'   => 'nullable|integer',
            'is_visible'            => 'nullable|boolean',
            'division_catalogue_id' => 'nullable|exists:division_catalogues,id',
            'insignia_catalogue_id' => 'nullable|exists:insignia_catalogues,id',
        ]);

        $division = Division::create([
            'division_title'        => $validated['division_title'],
            'division_description'  => $validated['division_description'] ?? null,
            'division_base_colour'  => $validated['division_base_colour'] ?? '#374151',
            'division_sort_order'   => $validated['division_sort_order'] ?? 0,
            'is_visible'            => $request->boolean('is_visible', false),
            'division_catalogue_id' => $validated['division_catalogue_id'] ?? null,
            'insignia_catalogue_id' => $validated['insignia_catalogue_id'] ?? null,
        ]);

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $division,
            'message' => lexicon('msg_SectionsPositions_SectionCreated', 'Section created successfully.'),
        ]);
    }

    public function update(Request $request, Division $section)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $validated = $request->validate([
            'division_title'        => 'required|string|max:255',
            'division_base_colour'  => 'required|string|max:9',
            'division_description'  => 'nullable|string',
            'division_sort_order'   => 'required|integer',
            'is_visible'            => 'nullable|boolean',
            'division_catalogue_id' => 'nullable|exists:division_catalogues,id',
            'insignia_catalogue_id' => 'nullable|exists:insignia_catalogues,id',
        ]);

        $section->update([
            'division_title'        => $validated['division_title'],
            'division_description'  => $validated['division_description'] ?? null,
            'division_base_colour'  => $validated['division_base_colour'],
            'division_sort_order'   => $validated['division_sort_order'],
            'is_visible'            => $request->boolean('is_visible', false),
            'division_catalogue_id' => $validated['division_catalogue_id'] ?? null,
            'insignia_catalogue_id' => $validated['insignia_catalogue_id'] ?? $section->insignia_catalogue_id,
        ]);

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => $section,
            'message' => lexicon('msg_SectionsPositions_SectionUpdated', 'Section updated successfully.'),
        ]);
    }

    public function destroy(Division $section)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $section->delete();

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_SectionDeleted', 'Section deleted successfully.'),
        ]);
    }

    public function generateDescription(Request $request, AILoreService $aiLoreService)
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $divisionTitle = trim((string) $request->input('division_title'));
        $hint = trim((string) $request->input('prompt_hint', ''));

        if (empty($divisionTitle)) {
            return response()->json([
                'error' => lexicon('msg_SectionsPositions_EnterNameFirst', 'Please enter a Section Name first.')
            ], 422);
        }

        try {
            $generatedText = $aiLoreService->generateSectionDescription($divisionTitle, $hint);
        } catch (\Exception $e) {
            $generatedText = "The {$divisionTitle} division is responsible for maintaining operational integrity and strategic objectives.";
        }

        return response()->json([
            'status'               => 'success',
            'division_description' => $generatedText,
        ]);
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $divisions = Division::with(['insigniaCatalogue', 'divisionCatalogue', 'assignments'])
            ->orderBy('division_sort_order')
            ->get()
            ->map(function ($division) {
                return [
                    'division_title'          => $division->division_title,
                    'division_description'    => $division->division_description,
                    'division_base_colour'    => $division->division_base_colour,
                    'division_sort_order'     => $division->division_sort_order,
                    'is_visible'              => (bool) $division->is_visible,
                    'division_catalogue_id'   => $division->division_catalogue_id,
                    'insignia_catalogue_id'   => $division->insignia_catalogue_id,
                    'insignia_catalogue_code' => $division->insigniaCatalogue?->catalogue_code,
                    'assignments'             => $division->assignments->map(function ($assignment) {
                        return [
                            'assignment_title'       => $assignment->assignment_title,
                            'assignment_description' => $assignment->assignment_description,
                            'maximum_occupants'      => $assignment->maximum_occupants,
                            'is_recruitment_open'    => (bool) $assignment->is_recruitment_open,
                            'is_visible'             => (bool) $assignment->is_visible,
                        ];
                    })->all(),
                ];
            });

        $manifest = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'divisions_assignments_manifest',
                'version'     => '2.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'divisions' => $divisions->all(),
        ];

        return response()->streamDownload(function () use ($manifest) {
            echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_divisions_assignments_export.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function template()
    {
        abort_unless(auth()->user()?->can('manage_sections'), 403, lexicon('msg_SectionsPositions_Unauthorised', 'Unauthorised access to Sections & Positions.'));

        $template = [
            'meta' => [
                'engine'      => 'MOWS Engine',
                'type'        => 'divisions_assignments_manifest',
                'version'     => '2.0',
                'exported_at' => now()->toIso8601String(),
            ],
            'divisions' => [
                [
                    'division_title'          => 'Command',
                    'division_description'    => 'Station command and executive management.',
                    'division_base_colour'    => '#dc2626',
                    'division_sort_order'     => 1,
                    'is_visible'              => true,
                    'division_catalogue_id'   => null,
                    'insignia_catalogue_id'   => null,
                    'insignia_catalogue_code' => 'writers',
                    'assignments'             => [
                        [
                            'assignment_title'       => 'Commanding Officer',
                            'assignment_description' => 'Overall station command.',
                            'maximum_occupants'      => 1,
                            'is_recruitment_open'    => true,
                            'is_visible'             => true,
                        ]
                    ]
                ]
            ],
        ];

        return response()->streamDownload(function () use ($template) {
            echo json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'mows_divisions_assignments_template.json', [
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

        if (!isset($data['divisions']) || !is_array($data['divisions'])) {
            return response()->json([
                'status'  => 'error',
                'message' => lexicon('msg_SectionsPositions_InvalidManifest', 'Invalid divisions manifest format.'),
            ], 422);
        }

        DB::transaction(function () use ($data, $request) {
            if ($request->input('mode') === 'replace') {
                Division::query()->delete();
            }

            foreach ($data['divisions'] as $divData) {
                $insigniaCatalogueId = $divData['insignia_catalogue_id'] ?? null;
                if (!$insigniaCatalogueId && !empty($divData['insignia_catalogue_code'])) {
                    $catalogue = InsigniaCatalogue::where('catalogue_code', $divData['insignia_catalogue_code'])->first();
                    $insigniaCatalogueId = $catalogue?->id;
                }

                $division = Division::updateOrCreate(
                    ['division_title' => $divData['division_title']],
                    [
                        'division_description'  => $divData['division_description'] ?? null,
                        'division_base_colour'  => $divData['division_base_colour'] ?? '#374151',
                        'division_sort_order'   => $divData['division_sort_order'] ?? 0,
                        'is_visible'            => $divData['is_visible'] ?? true,
                        'division_catalogue_id' => $divData['division_catalogue_id'] ?? null,
                        'insignia_catalogue_id' => $insigniaCatalogueId,
                    ]
                );

                if (!empty($divData['assignments']) && is_array($divData['assignments'])) {
                    foreach ($divData['assignments'] as $assignData) {
                        Assignment::updateOrCreate(
                            [
                                'division_id'      => $division->id,
                                'assignment_title' => $assignData['assignment_title'],
                            ],
                            [
                                'assignment_description' => $assignData['assignment_description'] ?? null,
                                'maximum_occupants'      => $assignData['maximum_occupants'] ?? 1,
                                'is_recruitment_open'    => $assignData['is_recruitment_open'] ?? true,
                                'is_visible'             => $assignData['is_visible'] ?? true,
                            ]
                        );
                    }
                }
            }
        });

        $this->clearDivisionCaches();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_SectionsPositions_ImportSuccess', 'Divisions and assignments imported successfully.'),
        ]);
    }
}
