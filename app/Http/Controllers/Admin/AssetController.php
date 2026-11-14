<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * List all images available in /public/images/general/
     */
    public function index()
    {
        abort_unless(auth()->user()->can('manage_themes') || auth()->user()->can('manage_pages'), 403, 'Unauthorised action.');

        $path = public_path('images/general');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $files = File::files($path);$images = [];

        foreach ($files as $file) {$images[] = [
                'filename' => $file->getFilename(),
                'url'      => asset('images/general/' . $file->getFilename()),
                'size'     => round($file->getSize() / 1024, 1) . ' KB',
            ];
        }

        $model =$images;

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_GeneralImage_Loaded', 'Assets loaded successfully.'),
            'data'     => $model,
            'images'   => $images,
            'redirect' => null,
        ], 200);
    }

    /**
     * Upload a new general site asset into /public/images/general/
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_themes') || auth()->user()->can('manage_pages'), 403, 'Unauthorised action.');

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:10240'], // Capped at 10 MB per hosting limit
        ]);

        $destinationPath = public_path('images/general');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file =$request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        $filename = 'gen_' . time() . '_' . Str::lower(Str::random(5)) . '.' . $extension;

        $file->move($destinationPath,$filename);

        $url = asset('images/general/' . $filename);$model = [
            'filename' => $filename,
            'url'      => $url,
        ];

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_GeneralImage_UploadSuccess', 'General site asset uploaded successfully.'),
            'data'     => $model,
            'url'      => $url,
            'filename' => $filename,
            'redirect' => null,
        ], 200);
    }

    /**
     * Delete a general site asset from /public/images/general/
     */
    public function destroy(Request $request)
    {
        abort_unless(auth()->user()->can('manage_themes') || auth()->user()->can('manage_pages'), 403, 'Unauthorised action.');

        $request->validate([
            'filename' => ['required', 'string'],
        ]);

        $filename = basename($request->filename);
        $filePath = public_path('images/general/' .$filename);

        if (File::exists($filePath)) {
            File::delete($filePath);
            $model = ['filename' =>$filename];

            return response()->json(['status' => 'success', 'message' => lexicon('msg_GeneralImage_Deleted', 'Asset removed successfully.'), 'data' => $model, 'redirect' => null], 200);
        }

        return response()->json([
            'status'   => 'error',
            'message'  => lexicon('msg_GeneralImage_NotFound', 'Asset file not found.'),
            'data'     => null,
            'redirect' => null,
        ], 404);
    }
}