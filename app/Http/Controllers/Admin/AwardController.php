<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Award;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\AwardCategory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class AwardController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/awards')
            ->where('is_published', true)
            ->firstOrFail();

        $awards = Award::orderBy('name')->get();
        $categories = AwardCategory::orderBy('name')->get();

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

        return view('admin.awards', compact('awards', 'systemPage', 'midShapes', 'topShapes', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $request->merge([
            'award_category_id' => $request->award_category_id ?: null,
            'mid_type' => $request->mid_type ?: null,
            'mid_image' => $request->mid_image ?: null,
            'mid_css' => $request->mid_css ?: null,
            'top_type' => $request->top_type ?: null,
            'top_image' => $request->top_image ?: null,
            'top_css' => $request->top_css ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'master_group' => 'required|in:BOTH,IC,OOC',
            'award_category_id' => 'nullable|exists:award_categories,id',
            'base_color' => 'required|string|max:50',
            'mid_type' => 'nullable|string|max:50',
            'mid_image' => 'nullable|string|max:255',
            'mid_css' => 'nullable|string|max:255',
            'top_type' => 'nullable|string|max:50',
            'top_image' => 'nullable|string|max:255',
            'top_css' => 'nullable|string|max:255',
        ]);

        Award::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_created', 'Award created successfully!')
        ]);
    }

    public function update(Request $request, Award $award)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $request->merge([
            'award_category_id' => $request->award_category_id ?: null,
            'mid_type' => $request->mid_type ?: null,
            'mid_image' => $request->mid_image ?: null,
            'mid_css' => $request->mid_css ?: null,
            'top_type' => $request->top_type ?: null,
            'top_image' => $request->top_image ?: null,
            'top_css' => $request->top_css ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'master_group' => 'required|in:BOTH,IC,OOC',
            'award_category_id' => 'nullable|exists:award_categories,id',
            'base_color' => 'required|string|max:50',
            'mid_type' => 'nullable|string|max:50',
            'mid_image' => 'nullable|string|max:255',
            'mid_css' => 'nullable|string|max:255',
            'top_type' => 'nullable|string|max:50',
            'top_image' => 'nullable|string|max:255',
            'top_css' => 'nullable|string|max:255',
        ]);

        $award->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_updated', 'Award updated successfully!')
        ]);
    }

    public function destroy(Award $award)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $award->delete();

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_deleted', 'Award deleted successfully!')
        ]);
    }

    public function images(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

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
            'images' => $images,
            'active_skin' => $activeSkin
        ]);
    }
}
