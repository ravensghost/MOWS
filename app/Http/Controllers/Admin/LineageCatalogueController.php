<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\LineageCatalogue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LineageCatalogueController extends Controller
{
    private function authoriseLineageAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_sections') || $user->can('manage_settings') || $user->can('access-admin')),
            403,
            lexicon('msg_Lineage_Unauthorised', 'Unauthorised access to Lineage Catalogues.')
        );
    }

    public function index(): View
    {
        $this->authoriseLineageAccess();

        $systemBook = Book::where('book_url', 'admin/lineages')
            ->where('is_published', true)
            ->with(['grid.gridModules', 'modules'])
            ->first();

        $lineages = LineageCatalogue::orderBy('lineage_title')->get();

        return view('admin.lineages', compact('systemBook', 'lineages'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authoriseLineageAccess();

        $validated = $request->validate([
            'lineage_title'              => 'required|string|max:255',
            'lineage_synopsis'           => 'nullable|string',
            'visual_trait_prompt'        => 'nullable|string',
            'negative_visual_trait_prompt' => 'nullable|string',
            'default_skin_tint_hex'      => 'nullable|string|max:9',
            'overlay_assets_json'        => 'nullable',
            'is_playable'                => 'nullable|boolean',
            'is_visible'                 => 'nullable|boolean',
        ]);

        $overlayAssets = is_array($validated['overlay_assets_json'] ?? null)
            ? $validated['overlay_assets_json']
            : (is_string($validated['overlay_assets_json'] ?? null) && $validated['overlay_assets_json'] !== ''
                ? json_decode($validated['overlay_assets_json'], true)
                : null);

        $lineage = LineageCatalogue::create([
            'lineage_title'                => $validated['lineage_title'],
            'lineage_synopsis'             => $validated['lineage_synopsis'] ?? null,
            'visual_trait_prompt'          => $validated['visual_trait_prompt'] ?? null,
            'negative_visual_trait_prompt' => $validated['negative_visual_trait_prompt'] ?? null,
            'default_skin_tint_hex'        => $validated['default_skin_tint_hex'] ?? null,
            'overlay_assets_json'          => $overlayAssets,
            'is_playable'                  => $request->boolean('is_playable', true),
            'is_visible'                   => $request->boolean('is_visible', true),
        ]);

        Cache::forget('widget_roster_sections');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lineage_Created', 'Lineage catalogue entry created successfully.'),
            'data'     => $lineage,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, LineageCatalogue $lineageCatalogue): JsonResponse
    {
        $this->authoriseLineageAccess();

        $validated = $request->validate([
            'lineage_title'                => 'required|string|max:255',
            'lineage_synopsis'             => 'nullable|string',
            'visual_trait_prompt'          => 'nullable|string',
            'negative_visual_trait_prompt' => 'nullable|string',
            'default_skin_tint_hex'        => 'nullable|string|max:9',
            'overlay_assets_json'          => 'nullable',
            'is_playable'                  => 'nullable|boolean',
            'is_visible'                   => 'nullable|boolean',
        ]);

        $overlayAssets = array_key_exists('overlay_assets_json', $validated)
            ? (is_array($validated['overlay_assets_json'])
                ? $validated['overlay_assets_json']
                : (is_string($validated['overlay_assets_json']) && $validated['overlay_assets_json'] !== ''
                    ? json_decode($validated['overlay_assets_json'], true)
                    : null))
            : $lineageCatalogue->overlay_assets_json;

        $lineageCatalogue->update([
            'lineage_title'                => $validated['lineage_title'],
            'lineage_synopsis'             => $validated['lineage_synopsis'] ?? null,
            'visual_trait_prompt'          => $validated['visual_trait_prompt'] ?? null,
            'negative_visual_trait_prompt' => $validated['negative_visual_trait_prompt'] ?? null,
            'default_skin_tint_hex'        => $validated['default_skin_tint_hex'] ?? null,
            'overlay_assets_json'          => $overlayAssets,
            'is_playable'                  => $request->has('is_playable') ? $request->boolean('is_playable') : (bool) $lineageCatalogue->is_playable,
            'is_visible'                   => $request->has('is_visible') ? $request->boolean('is_visible') : (bool) $lineageCatalogue->is_visible,
        ]);

        Cache::forget('widget_roster_sections');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lineage_Updated', 'Lineage catalogue entry updated successfully.'),
            'data'     => $lineageCatalogue,
            'redirect' => null,
        ], 200);
    }

    public function destroy(LineageCatalogue $lineageCatalogue): JsonResponse
    {
        $this->authoriseLineageAccess();

        $lineageCatalogue->delete();
        Cache::forget('widget_roster_sections');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Lineage_Deleted', 'Lineage catalogue entry deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}