<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // <-- 1. Add Cache import
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
        Cache::forget('widget_manifest_sections'); // In case manifest_descriptions caches sections too
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
        ]);

        $section = Section::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? '#374151',
            'order'       => $validated['order'] ?? 0,
        ]);

        $this->clearSectionCaches(); // <-- 2. Clear cache on create

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
        ]);

        $section->update($validated);

        $this->clearSectionCaches(); // <-- 3. Clear cache on update

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

        $this->clearSectionCaches(); // <-- 4. Clear cache on delete

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
}
