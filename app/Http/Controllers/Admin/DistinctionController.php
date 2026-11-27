<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Distinction;
use App\Models\DistinctionCatalogue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class DistinctionController extends Controller
{
    public function index(): View
    {
        abort_unless(
            auth()->user()?->can('issue_awards'),
            403,
            lexicon('msg_Awards_Unauthorised', 'Unauthorised action.')
        );

        $systemBook = Book::forUrl('/admin/distinctions')->publishedWithLayout()->firstOrFail()

        $distinctions = Distinction::with('catalogue')->orderBy('distinction_title')->get();
        $catalogues = DistinctionCatalogue::orderBy('catalogue_title')->get();

        $cssPath = resource_path('css/distinctions.css');

        $midShapes = [];
        $topShapes = [];

        if (File::exists($cssPath)) {
            $cssContent = File::get($cssPath);

            preg_match_all('/\.distinction-ml-([a-zA-Z0-9_-]+)/', $cssContent, $mlMatches);
            if (! empty($mlMatches[1])) {
                foreach (array_unique($mlMatches[1]) as $shape) {
                    $midShapes[$shape] = str_replace('-', ' ', ucwords($shape));
                }
            }

            preg_match_all('/\.distinction-tl-([a-zA-Z0-9_-]+)/', $cssContent, $tlMatches);
            if (! empty($tlMatches[1])) {
                foreach (array_unique($tlMatches[1]) as $shape) {
                    $topShapes[$shape] = str_replace('-', ' ', ucwords($shape));
                }
            }
        }

        return view('admin.distinctions', [
            'distinctions' => $distinctions,
            'systemBook'   => $systemBook,
            'midShapes'    => $midShapes,
            'topShapes'    => $topShapes,
            'catalogues'   => $catalogues,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('issue_awards'),
            403,
            lexicon('msg_Awards_Unauthorised', 'Unauthorised action.')
        );

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

        $distinction = Distinction::create($validated);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_distinction_created', 'Distinction created successfully!'),
            'data'     => $distinction,
            'redirect' => null,
        ], 200);
    }

    public function update(Request $request, Distinction $distinction): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('issue_awards'),
            403,
            lexicon('msg_Awards_Unauthorised', 'Unauthorised action.')
        );

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
            'status'   => 'success',
            'message'  => lexicon('msg_distinction_updated', 'Distinction updated successfully!'),
            'data'     => $distinction,
            'redirect' => null,
        ], 200);
    }

    public function destroy(Distinction $distinction): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('issue_awards'),
            403,
            lexicon('msg_Awards_Unauthorised', 'Unauthorised action.')
        );

        $distinction->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_distinction_deleted', 'Distinction deleted successfully!'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function images(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('issue_awards'),
            403,
            lexicon('msg_Awards_Unauthorised', 'Unauthorised action.')
        );

        $activeSkinArray = Cache::get('active_skin_default_array');
        $activeSkin = $activeSkinArray['folder_name'] ?? 'default';

        $path = public_path("skins/{$activeSkin}/images/distinctions");

        if (! File::exists($path)) {
            return response()->json([
                'status'  => 'success',
                'message' => lexicon('msg_distinction_images_loaded', 'Distinction images retrieved.'),
                'data'    => [
                    'images'      => [],
                    'active_skin' => $activeSkin,
                ],
                'redirect' => null,
            ], 200);
        }

        $files = File::files($path);
        $images = [];

        foreach ($files as $file) {
            $images[] = $file->getFilename();
        }

        sort($images);

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_distinction_images_loaded', 'Distinction images retrieved.'),
            'data'    => [
                'images'      => $images,
                'active_skin' => $activeSkin,
            ],
            'redirect' => null,
        ], 200);
    }
}