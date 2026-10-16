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
        $request->validate(['name' => 'required|string']);
        FormField::create([
            'name' => $request->name,
            'type' => 'custom_form',
            'form_type' => 'form'
        ]);
        return response()->json(['success' => true]);
    }

    public function updateForm(Request $request, $id)
    {
        $request->validate(['name' => 'required|string']);
        FormField::where('id', $id)->update(['name' => $request->name]);
        return response()->json(['success' => true]);
    }

    public function destroyForm($id)
    {
        FormField::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function storeField(Request $request)
    {
        FormField::create([
            'name' => $request->name,
            'type' => $request->type,
            'form_type' => 'application',
            'form_linked' => $request->form_linked,
            'is_required' => $request->has('is_required'),
            'options' => $request->options,
            'order' => 0
        ]);
        return response()->json(['success' => true]);
    }

    public function updateField(Request $request, $id)
    {
        FormField::where('id', $id)->update([
            'name' => $request->name,
            'type' => $request->type,
            'form_linked' => $request->form_linked,
            'is_required' => $request->has('is_required'),
            'options' => $request->options
        ]);
        return response()->json(['success' => true]);
    }

    public function destroyField($id)
    {
        FormField::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
