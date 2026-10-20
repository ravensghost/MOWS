<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\FormField;

class FormManagerController extends Controller
{
    public function index()
    {
        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/forms')
            ->where('is_published', true)
            ->firstOrFail();

        $forms = FormField::where('form_type', 'form')->orderBy('name')->get();
        $fields = FormField::where('form_type', 'application')->orderBy('order')->get();

        return view('admin.forms', compact('systemPage', 'forms', 'fields'));
    }

    public function storeForm(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'is_visible' => 'nullable|boolean',
        ]);

        FormField::create([
            'name'       => $request->name,
            'type'       => 'custom_form',
            'form_type'  => 'form',
            'is_visible' => $request->boolean('is_visible', false),
        ]);

        return response()->json(['success' => true]);
    }

    public function updateForm(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'is_visible' => 'nullable|boolean',
        ]);

        FormField::where('id', $id)->update([
            'name'       => $request->name,
            'is_visible' => $request->boolean('is_visible', false),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyForm($id)
    {
        FormField::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function storeField(Request $request)
    {
        $field = new FormField();
        $hasArrayCast = $field->hasCast('options', ['array', 'json', 'collection']);

        $groups = array_values((array) $request->input('service_record_groups', []));
        $options = $request->type === 'service_record'
            ? ($hasArrayCast ? $groups : json_encode($groups))
            : $request->options;

        FormField::create([
            'name'        => $request->name,
            'type'        => $request->type,
            'form_type'   => 'application',
            'form_linked' => $request->form_linked,
            'is_required' => $request->boolean('is_required', false),
            'is_visible'  => $request->boolean('is_visible', false),
            'options'     => $options,
            'order'       => $request->input('order', 0),
        ]);

        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        $field = FormField::findOrFail($id);
        $hasArrayCast = $field->hasCast('options', ['array', 'json', 'collection']);

        $groups = array_values((array) $request->input('service_record_groups', []));
        $options = $request->type === 'service_record'
            ? ($hasArrayCast ? $groups : json_encode($groups))
            : $request->options;

        $field->update([
            'name'        => $request->name,
            'type'        => $request->type,
            'form_type'   => 'application',
            'form_linked' => $request->form_linked,
            'is_required' => $request->boolean('is_required', false),
            'is_visible'  => $request->boolean('is_visible', false),
            'options'     => $options,
            'order'       => $request->input('order', $field->order),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyField($id)
    {
        FormField::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
