<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Metadata;

class MetadataController extends Controller
{
    public function index()
    {
        $systemPage = Book::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/forms')
            ->where('is_published', true)
            ->firstOrFail();

        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();
        $fields = Metadata::where('meta_mode', 'application')->orderBy('metadata_sort_order')->get();

        return view('admin.forms', compact('systemPage', 'forms', 'fields'));
    }

    public function storeForm(Request $request)
    {
        $request->validate([
            'metadata_title' => 'required|string|max:255',
            'is_visible'     => 'nullable|boolean',
        ]);

        Metadata::create([
            'metadata_title' => $request->metadata_title,
            'metadata_mode'  => 'custom_form',
            'meta_mode'      => 'form',
            'is_visible'     => $request->boolean('is_visible', false),
        ]);

        return response()->json(['success' => true]);
    }

    public function updateForm(Request $request, $id)
    {
        $request->validate([
            'metadata_title' => 'required|string|max:255',
            'is_visible'     => 'nullable|boolean',
        ]);

        Metadata::where('id', $id)->update([
            'metadata_title' => $request->metadata_title,
            'is_visible'     => $request->boolean('is_visible', false),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyForm($id)
    {
        Metadata::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function storeField(Request $request)
    {
        $field = new Metadata();
        $hasArrayCast = $field->hasCast('metadata_payloads', ['array', 'json', 'collection']);

        $groups = array_values((array) $request->input('service_record_groups', []));
        $metadataPayloads = $request->metadata_mode === 'service_record'
            ? ($hasArrayCast ? $groups : json_encode($groups))
            : $request->metadata_payloads;

        Metadata::create([
            'metadata_title'      => $request->metadata_title,
            'metadata_mode'       => $request->metadata_mode,
            'meta_mode'           => 'application',
            'linked_metadata'     => $request->linked_metadata,
            'is_required'         => $request->boolean('is_required', false),
            'is_visible'          => $request->boolean('is_visible', false),
            'metadata_payloads'   => $metadataPayloads,
            'metadata_sort_order' => $request->input('metadata_sort_order', 0),
        ]);

        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        $field = Metadata::findOrFail($id);
        $hasArrayCast = $field->hasCast('metadata_payloads', ['array', 'json', 'collection']);

        $groups = array_values((array) $request->input('service_record_groups', []));
        $metadataPayloads = $request->metadata_mode === 'service_record'
            ? ($hasArrayCast ? $groups : json_encode($groups))
            : $request->metadata_payloads;

        $field->update([
            'metadata_title'      => $request->metadata_title,
            'metadata_mode'       => $request->metadata_mode,
            'meta_mode'           => 'application',
            'linked_metadata'     => $request->linked_metadata,
            'is_required'         => $request->boolean('is_required', false),
            'is_visible'          => $request->boolean('is_visible', false),
            'metadata_payloads'   => $metadataPayloads,
            'metadata_sort_order' => $request->input('metadata_sort_order', $field->metadata_sort_order),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyField($id)
    {
        Metadata::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
