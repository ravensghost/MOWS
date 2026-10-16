<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\RankSet;
use App\Models\Section;
use App\Models\Skin;
use App\Models\Setting;
use App\Models\Page;
use App\Models\Ranks;

class RankController extends Controller
{
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
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'mid_type' => 'required|in:css,image',
            'mid_image' => 'nullable|string',
            'mid_css' => 'nullable|string',
            'mid_color' => 'nullable|string|max:50',
            'top_type' => 'required|in:css,image',
            'top_image' => 'nullable|string',
            'top_css' => 'nullable|string',
            'top_color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer'
        ]);

        Ranks::create($validated);
        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Created', 'Rank created successfully.')]);
    }

    public function update(Request $request, Ranks $rank)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated = $request->validate([
            'rank_set_id' => 'required|exists:rank_sets,id',
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'mid_type' => 'required|in:css,image',
            'mid_image' => 'nullable|string',
            'mid_css' => 'nullable|string',
            'mid_color' => 'nullable|string|max:50',
            'top_type' => 'required|in:css,image',
            'top_image' => 'nullable|string',
            'top_css' => 'nullable|string',
            'top_color' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer'
        ]);

        $rank->update($validated);
        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Updated', 'Rank updated successfully.')]);
    }

    public function destroy(Ranks $rank)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $rank->delete();
        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Deleted', 'Rank deleted successfully.')]);
    }

    public function reorder(Request $request)
    {
        abort_unless(auth()->user()->can('manage_ranks'), 403, lexicon('msg_Ranks_Unauthorised', 'Unauthorised access to Rank Management.'));

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:ranks,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            Ranks::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Ranks_Reordered', 'Ranks reordered successfully.')]);
    }
}
