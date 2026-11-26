<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Metadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetadataController extends Controller
{
    private function authoriseMetadataAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_forms') || $user->can('manage_metadata') || $user->can('access-admin')),
            403,
            lexicon('msg_Metadata_Unauthorised', 'Unauthorised access to Metadata & Forms Manager.')
        );
    }

    public function index(): View
    {
        $this->authoriseMetadataAccess();

        $systemBook = Book::with(['grid.gridModules', 'modules'])
            ->where('book_url', 'admin/forms')
            ->where('is_published', true)
            ->firstOrFail();

        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();
        $fields = Metadata::where('meta_mode', 'application')->orderBy('metadata_sort_order')->get();

        return view('admin.meta', compact('systemBook', 'forms', 'fields'));
    }

    public function storeForm(Request $request): JsonResponse
    {
        $this->authoriseMetadataAccess();

        $request->validate([
            'metadata_title' => 'required|string|max:255',
            'is_visible'     => 'nullable|boolean',
        ]);

        $form = Metadata::create([
            'metadata_title' => $request->input('metadata_title'),
            'metadata_mode'  => 'custom_form',
            'meta_mode'      => 'form',
            'is_visible'     => $request->boolean('is_visible', false),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('admin.metadata.form_created', 'Metadata form created successfully.'),
            'data'     => $form,
            'redirect' => null,
        ], 200);
    }

    public function updateForm(Request $request, int|string $id): JsonResponse
    {
        $this->authoriseMetadataAccess();

        $request->validate([
            'metadata_title' => 'required|string|max:255',
            'is_visible'     => 'nullable|boolean',
        ]);

        $form = Metadata::findOrFail($id);

        $form->update([
            'metadata_title' => $request->input('metadata_title'),
            'is_visible'     => $request->boolean('is_visible', false),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('admin.metadata.form_updated', 'Metadata form updated successfully.'),
            'data'     => $form,
            'redirect' => null,
        ], 200);
    }

    public function destroyForm(int|string $id): JsonResponse
    {
        $this->authoriseMetadataAccess();

        $form = Metadata::findOrFail($id);
        $form->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('admin.metadata.form_deleted', 'Metadata form deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }

    public function storeField(Request $request): JsonResponse
    {
        $this->authoriseMetadataAccess();

        $request->validate([
            'metadata_title'      => 'required|string|max:255',
            'metadata_mode'       => 'required|string|max:100',
            'linked_metadata'     => 'nullable|string|max:255',
            'metadata_payloads'   => 'nullable',
            'metadata_sort_order' => 'nullable|integer',
            'is_required'         => 'nullable|boolean',
            'is_visible'          => 'nullable|boolean',
        ]);

        $prototype = new Metadata;
        $hasArrayCast = $prototype->hasCast('metadata_payloads', ['array', 'json', 'collection']);

        $groups = array_values((array) $request->input('service_record_groups', []));
        $metadataPayloads = $request->input('metadata_mode') === 'service_record'
            ? ($hasArrayCast ? $groups : json_encode($groups))
            : $request->input('metadata_payloads');

        $field = Metadata::create([
            'metadata_title'      => $request->input('metadata_title'),
            'metadata_mode'       => $request->input('metadata_mode'),
            'meta_mode'           => 'application',
            'linked_metadata'     => $request->input('linked_metadata'),
            'is_required'         => $request->boolean('is_required', false),
            'is_visible'          => $request->boolean('is_visible', false),
            'metadata_payloads'   => $metadataPayloads,
            'metadata_sort_order' => $request->input('metadata_sort_order', 0),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('admin.metadata.field_created', 'Metadata field created successfully.'),
            'data'     => $field,
            'redirect' => null,
        ], 200);
    }

    public function updateField(Request $request, int|string $id): JsonResponse
    {
        $this->authoriseMetadataAccess();

        $request->validate([
            'metadata_title'      => 'required|string|max:255',
            'metadata_mode'       => 'required|string|max:100',
            'linked_metadata'     => 'nullable|string|max:255',
            'metadata_payloads'   => 'nullable',
            'metadata_sort_order' => 'nullable|integer',
            'is_required'         => 'nullable|boolean',
            'is_visible'          => 'nullable|boolean',
        ]);

        $field = Metadata::findOrFail($id);
        $hasArrayCast = $field->hasCast('metadata_payloads', ['array', 'json', 'collection']);

        $groups = array_values((array) $request->input('service_record_groups', []));
        $metadataPayloads = $request->input('metadata_mode') === 'service_record'
            ? ($hasArrayCast ? $groups : json_encode($groups))
            : $request->input('metadata_payloads');

        $field->update([
            'metadata_title'      => $request->input('metadata_title'),
            'metadata_mode'       => $request->input('metadata_mode'),
            'meta_mode'           => 'application',
            'linked_metadata'     => $request->input('linked_metadata'),
            'is_required'         => $request->boolean('is_required', false),
            'is_visible'          => $request->boolean('is_visible', false),
            'metadata_payloads'   => $metadataPayloads,
            'metadata_sort_order' => $request->input('metadata_sort_order', $field->metadata_sort_order),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('admin.metadata.field_updated', 'Metadata field updated successfully.'),
            'data'     => $field,
            'redirect' => null,
        ], 200);
    }

    public function destroyField(int|string $id): JsonResponse
    {
        $this->authoriseMetadataAccess();

        $field = Metadata::findOrFail($id);
        $field->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('admin.metadata.field_deleted', 'Metadata field deleted successfully.'),
            'data'     => null,
            'redirect' => null,
        ], 200);
    }
}