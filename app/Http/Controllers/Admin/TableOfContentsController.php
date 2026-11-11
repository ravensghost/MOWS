<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\TableOfContent;
use App\Models\TableOfContentParameter;
use App\Models\Book;
use Spatie\Permission\Models\Permission;

class TableOfContentsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $systemPage = Book::where('book_url', 'admin/navigation')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($query) {
                    $query->orderBy('module_sort_order');
                }
            ])
            ->firstOrFail();

        $links = TableOfContent::whereNull('parent_entry_id')
            ->with('children')
            ->orderBy('display_location')
            ->orderBy('group_sort_order')
            ->orderBy('group_title')
            ->orderBy('entry_sort_order')
            ->get();

        $parentOptions = TableOfContent::whereNull('parent_entry_id')
            ->where('display_location', 'primary')
            ->orderBy('translation_code_key')
            ->get();

        $allOptions = TableOfContentParameter::orderBy('parameter_mode')
            ->orderBy('parameter_sort_order')
            ->get();

        $grouped = $allOptions->groupBy('parameter_mode');

        $permissions = Permission::orderBy('name')->get();

        return view('admin.nav', [
            'systemPage'        => $systemPage,
            'links'             => $links,
            'parentOptions'     => $parentOptions,
            'allOptions'        => $allOptions,
            'groupOptions'      => $grouped->get('group', collect()),
            'locationOptions'   => $grouped->get('display_location', collect()),
            'visibilityOptions' => $grouped->get('access_clearance', collect()),
            'permissions'       => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'parent_entry_id'      => 'nullable|exists:table_of_contents,id',
            'translation_code_key' => 'required|string|max:255',
            'destination_url'      => 'required|string|max:255',
            'display_location'     => 'required|string|exists:table_of_content_parameters,parameter_value',
            'access_clearance'     => 'required|string|exists:table_of_content_parameters,parameter_value',
            'required_permission'  => 'nullable|string|exists:permissions,name',
            'window_target'        => 'required|in:_self,_blank',
            'entry_sort_order'     => 'required|integer',
            'group_title'          => 'nullable|string|max:255',
            'group_sort_order'     => 'nullable|integer',
            'is_visible'           => 'nullable|boolean',
        ]);

        $validated['group_sort_order'] = $validated['group_sort_order'] ?? 0;
        $validated['is_visible'] = $request->boolean('is_visible', false);

        TableOfContent::create($validated);

        if (!empty($validated['group_title']) && isset($validated['group_sort_order'])) {
            TableOfContent::where('group_title', $validated['group_title'])
                ->update(['group_sort_order' => $validated['group_sort_order']]);
        }

        Cache::forget('table_of_contents_all_array');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Navigation_Created', 'Navigation item created successfully.')
        ]);
    }

    public function update(Request $request, TableOfContent $link)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'parent_entry_id'      => 'nullable|exists:table_of_contents,id',
            'translation_code_key' => 'required|string|max:255',
            'destination_url'      => 'required|string|max:255',
            'display_location'     => 'required|string|exists:table_of_content_parameters,parameter_value',
            'access_clearance'     => 'required|string|exists:table_of_content_parameters,parameter_value',
            'required_permission'  => 'nullable|string|exists:permissions,name',
            'window_target'        => 'required|in:_self,_blank',
            'entry_sort_order'     => 'required|integer',
            'group_title'          => 'nullable|string|max:255',
            'group_sort_order'     => 'nullable|integer',
            'is_visible'           => 'nullable|boolean',
        ]);

        $validated['group_sort_order'] = $validated['group_sort_order'] ?? 0;
        $validated['is_visible'] = $request->boolean('is_visible', false);
        $link->update($validated);

        if (!empty($validated['group_title']) && isset($validated['group_sort_order'])) {
            TableOfContent::where('group_title', $validated['group_title'])
                ->update(['group_sort_order' => $validated['group_sort_order']]);
        }

        Cache::forget('table_of_contents_all_array');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Navigation_Updated', 'Navigation item updated successfully.')
        ]);
    }

    public function destroy(TableOfContent $link)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $link->delete();
        Cache::forget('table_of_contents_all_array');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Navigation_Removed', 'Navigation item removed successfully.')
        ]);
    }

    public function optionsIndex()
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $systemPage = Book::where('book_url', 'admin/navigation/options')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($query) {
                    $query->orderBy('module_sort_order');
                }
            ])
            ->firstOrFail();

        $options = TableOfContentParameter::orderBy('parameter_mode')
            ->orderBy('parameter_sort_order')
            ->get();

        return view('admin.nav-options', compact('options', 'systemPage'));
    }

    public function storeOption(Request $request)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'parameter_mode'       => 'required|string|max:50',
            'parameter_value'      => 'required|string|max:255',
            'parameter_label'      => 'nullable|string|max:255',
            'parameter_sort_order' => 'nullable|integer',
        ]);

        $validated['parameter_sort_order'] = $validated['parameter_sort_order'] ?? 0;
        $validated['parameter_label'] = $validated['parameter_label'] ?? $validated['parameter_value'];

        TableOfContentParameter::create($validated);
        Cache::forget('table_of_contents_all_array');

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Navigation_OptionAdded', 'Navigation option added successfully.')
        ]);
    }

    public function updateOption(Request $request, TableOfContentParameter $option)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'parameter_mode'       => 'required|string|max:50',
            'parameter_value'      => 'required|string|max:255',
            'parameter_label'      => 'nullable|string|max:255',
            'parameter_sort_order' => 'nullable|integer',
        ]);

        $validated['parameter_sort_order'] = $validated['parameter_sort_order'] ?? 0;

        $option->update($validated);
        Cache::forget('table_of_contents_all_array');

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Navigation_OptionUpdated', 'Navigation option updated successfully.')
        ]);
    }

    public function destroyOption(TableOfContentParameter $option)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $option->delete();
        Cache::forget('table_of_contents_all_array');

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Navigation_OptionDeleted', 'Navigation option deleted successfully.')
        ]);
    }
}
