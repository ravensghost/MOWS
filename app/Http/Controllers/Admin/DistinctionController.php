<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distinction;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\DistinctionCatalogue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class DistinctionController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/awards')
            ->where('is_published', true)
            ->firstOrFail();

        $distinctions = Distinction::orderBy('distinction_title')->get();
        $categories = DistinctionCatalogue::orderBy('catalogue_title')->get();

        $activeSkinArray = Cache::get('active_skin_default_array');
        $skinFolder = $activeSkinArray['folder_name'] ?? 'default';

        $cssPath = resource_path('css/awards.css');
        $midShapes = [];
        $topShapes = [];

        if (File::exists($cssPath)) {
            $cssContent = File::get($cssPath);

            preg_match_all('/\.award-ml-([a-zA-Z0-9_-]+)/', $cssContent, $mlMatches);
            if (!empty($mlMatches[1])) {
                foreach (array_unique($mlMatches[1]) as $shape) {
                    $midShapes[$shape] = str_replace('-', ' ', ucwords($shape));
                }
            }

            preg_match_all('/\.award-tl-([a-zA-Z0-9_-]+)/', $cssContent, $tlMatches);
            if (!empty($tlMatches[1])) {
                foreach (array_unique($tlMatches[1]) as $shape) {
                    $topShapes[$shape] = str_replace('-', ' ', ucwords($shape));
                }
            }
        }

        return view('admin.awards', compact('distinctions', 'systemPage', 'midShapes', 'topShapes', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $request->merge([
            'distinction_catalogue_id' => $request->distinction_catalogue_id ?: null,
            'mid_layer_mode'           => $request->mid_layer_mode ?: null,
            'mid_layer_asset'          => $request->mid_layer_asset ?: null,
            'mid_layer_modifier'       => $request->mid_layer_modifier ?: null,
            'top_layer_mode'           => $request->top_layer_mode ?: null,
            'top_layer_asset'          => $request->top_layer_asset ?: null,
            'top_layer_modifier'       => $request->top_layer_modifier ?: null,
        ]);

        $validated = $request->validate([
            'distinction_title'        => 'required|string|max:255',
            'distinction_description'  => 'nullable|string',
            'scope_target'             => 'required|in:BOTH,IC,OOC',
            'distinction_catalogue_id' => 'nullable|exists:distinction_catalogues,id',
            'distinction_base_colour'  => 'required|string|max:50',
            'mid_layer_mode'           => 'nullable|string|max:50',
            'mid_layer_asset'          => 'nullable|string|max:255',
            'mid_layer_modifier'       => 'nullable|string|max:255',
            'top_layer_mode'           => 'nullable|string|max:50',
            'top_layer_asset'          => 'nullable|string|max:255',
            'top_layer_modifier'       => 'nullable|string|max:255',
        ]);

        Distinction::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_award_created', 'Distinction created successfully!')
        ]);
    }

    public function update(Request $request, Distinction $distinction)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $request->merge([
            'distinction_catalogue_id' => $request->distinction_catalogue_id ?: null,
            'mid_layer_mode'           => $request->mid_layer_mode ?: null,
            'mid_layer_asset'          => $request->mid_layer_asset ?: null,
            'mid_layer_modifier'       => $request->mid_layer_modifier ?: null,
            'top_layer_mode'           => $request->top_layer_mode ?: null,
            'top_layer_asset'          => $request->top_layer_asset ?: null,
            'top_layer_modifier'       => $request->top_layer_modifier ?: null,
        ]);

        $validated = $request->validate([
            'distinction_title'        => 'required|string|max:255',
            'distinction_description'  => 'nullable|string',
            'scope_target'             => 'required|in:BOTH,IC,OOC',
            'distinction_catalogue_id' => 'nullable|exists:distinction_catalogues,id',
            'distinction_base_colour'  => 'required|string|max:50',
            'mid_layer_mode'           => 'nullable|string|max:50',
            'mid_layer_asset'          => 'nullable|string|max:255',
            'mid_layer_modifier'       => 'nullable|string|max:255',
            'top_layer_mode'           => 'nullable|string|max:50',
            'top_layer_asset'          => 'nullable|string|max:255',
            'top_layer_modifier'       => 'nullable|string|max:255',
        ]);

        $distinction->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_award_updated', 'Distinction updated successfully!')
        ]);
    }

    public function destroy(Distinction $distinction)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $distinction->delete();

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_award_deleted', 'Distinction deleted successfully!')
        ]);
    }

    public function images(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Authorised action.');

        $activeSkinArray = Cache::get('active_skin_default_array');
        $activeSkin = $activeSkinArray['folder_name'] ?? 'default';
        $path = public_path("skins/{$activeSkin}/images/awards");

        if (!File::exists($path)) {
            return response()->json(['images' => []]);
        }

        $files = File::files($path);
        $images = [];

        foreach ($files as $file) {
            $images[] = $file->getFilename();
        }

        sort($images);

        return response()->json([
            'images'      => $images,
            'active_skin' => $activeSkin
        ]);
    }
}
