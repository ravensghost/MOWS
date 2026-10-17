<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GeneralImageController extends Controller
{
    /**
     * List all images available in /public/images/general/
     */
    public function index()
    {
        abort_unless(auth()->user()->can('manage_themes') || auth()->user()->can('manage_pages'), 403, 'Unauthorized action.');

        $path = public_path('images/general');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $files = File::files($path);
        $images = [];

        foreach ($files as $file) {
            $images[] = [
                'filename' => $file->getFilename(),
                'url'      => asset('images/general/' . $file->getFilename()),
                'size'     => round($file->getSize() / 1024, 1) . ' KB',
            ];
        }

        return response()->json([
            'status' => 'success',
            'images' => $images,
        ]);
    }

    /**
     * Upload a new general site asset into /public/images/general/
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_themes') || auth()->user()->can('manage_pages'), 403, 'Unauthorized action.');

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:10240'], // Capped at 10 MB per hosting limit
        ]);

        $destinationPath = public_path('images/general');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        $filename = 'gen_' . time() . '_' . Str::lower(Str::random(5)) . '.' . $extension;

        $file->move($destinationPath, $filename);

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_GeneralImage_UploadSuccess', 'General site asset uploaded successfully.'),
            'url'     => asset('images/general/' . $filename),
            'filename' => $filename,
        ]);
    }

    /**
     * Delete a general site asset from /public/images/general/
     */
    public function destroy(Request $request)
    {
        abort_unless(auth()->user()->can('manage_themes') || auth()->user()->can('manage_pages'), 403, 'Unauthorized action.');

        $request->validate([
            'filename' => ['required', 'string'],
        ]);

        $filename = basename($request->filename);
        $filePath = public_path('images/general/' . $filename);

        if (File::exists($filePath)) {
            File::delete($filePath);
            return response()->json([
                'status'  => 'success',
                'message' => lexicon('msg_GeneralImage_Deleted', 'Asset removed successfully.'),
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => lexicon('msg_GeneralImage_NotFound', 'Asset file not found.'),
        ], 404);
    }
}
