<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\NavigationLink;
use App\Models\NavigationOption;
use App\Models\Page;
use Spatie\Permission\Models\Permission;

class NavigationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $systemPage = Page::where('slug', 'admin/navigation')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $links = NavigationLink::whereNull('parent_id')
            ->with('children')
            ->orderBy('location')
            ->orderBy('group_order')
            ->orderBy('group_name')
            ->orderBy('order')
            ->get();

        $parentOptions = NavigationLink::whereNull('parent_id')
            ->where('location', 'primary')
            ->orderBy('lexicon_key')
            ->get();

        $allOptions = NavigationOption::orderBy('option_type')
            ->orderBy('sort_order')
            ->get();

        $grouped = $allOptions->groupBy('option_type');

        $permissions = Permission::orderBy('name')->get();

        return view('admin.nav', [
            'systemPage'        => $systemPage,
            'links'             => $links,
            'parentOptions'     => $parentOptions,
            'allOptions'        => $allOptions,
            'groupOptions'      => $grouped->get('group', collect()),
            'locationOptions'   => $grouped->get('location', collect()),
            'visibilityOptions' => $grouped->get('visibility', collect()),
            'permissions'       => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'parent_id'           => 'nullable|exists:navigation_links,id',
            'lexicon_key'         => 'required|string',
            'url'                 => 'required|string',
            'location'            => 'required|string|exists:navigation_options,value',
            'visibility'          => 'required|string|exists:navigation_options,value',
            'required_permission' => 'nullable|string|exists:permissions,name',
            'target'              => 'required|in:_self,_blank',
            'order'               => 'required|integer',
            'group_name'          => 'nullable|string|max:255',
            'group_order'         => 'nullable|integer'
        ]);

        NavigationLink::create($validated);

        if (!empty($validated['group_name']) && isset($validated['group_order'])) {
            NavigationLink::where('group_name', $validated['group_name'])
                ->update(['group_order' => $validated['group_order']]);
        }

        Cache::forget('navigation_links_all_array');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Navigation_Saved', 'Navigation item saved successfully.')
        ]);
    }

    public function update(Request $request, NavigationLink $link)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'parent_id'           => 'nullable|exists:navigation_links,id',
            'lexicon_key'         => 'required|string',
            'url'                 => 'required|string',
            'location'            => 'required|string|exists:navigation_options,value',
            'visibility'          => 'required|string|exists:navigation_options,value',
            'required_permission' => 'nullable|string|exists:permissions,name',
            'target'              => 'required|in:_self,_blank',
            'order'               => 'required|integer',
            'group_name'          => 'nullable|string|max:255',
            'group_order'         => 'nullable|integer'
        ]);

        $link->update($validated);

        if (!empty($validated['group_name']) && isset($validated['group_order'])) {
            NavigationLink::where('group_name', $validated['group_name'])
                ->update(['group_order' => $validated['group_order']]);
        }

        Cache::forget('navigation_links_all_array');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Navigation_Updated', 'Navigation item updated successfully.')
        ]);
    }

    public function destroy(NavigationLink $link)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $link->delete();
        Cache::forget('navigation_links_all_array');

        return response()->json([
            'success' => true,
            'message' => lexicon('msg_Navigation_Removed', 'Navigation item removed successfully.')
        ]);
    }

    public function optionsIndex()
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $systemPage = Page::where('slug', 'admin/navigation/options')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $options = NavigationOption::orderBy('option_type')
            ->orderBy('sort_order')
            ->get();

        return view('admin.nav-options', compact('options', 'systemPage'));
    }

    public function storeOption(Request $request)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'option_type' => 'required|string|max:50',
            'value'       => 'required|string|max:255',
            'label'       => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['label'] = $validated['label'] ?? $validated['value'];

        NavigationOption::create($validated);
        Cache::forget('navigation_links_all_array');

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Navigation_OptionAdded', 'Navigation option added successfully.')
        ]);
    }

    public function updateOption(Request $request, NavigationOption $option)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $validated = $request->validate([
            'option_type' => 'required|string|max:50',
            'value'       => 'required|string|max:255',
            'label'       => 'nullable|string|max:255',
            'sort_order'  => 'nullable|integer',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $option->update($validated);
        Cache::forget('navigation_links_all_array');

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Navigation_OptionUpdated', 'Navigation option updated successfully.')
        ]);
    }

    public function destroyOption(NavigationOption $option)
    {
        abort_unless(auth()->user()->can('manage_navigation'), 403, lexicon('msg_Navigation_Unauthorised', 'Unauthorised access to Navigation Management.'));

        $option->delete();
        Cache::forget('navigation_links_all_array');

        return response()->json([
            'status'  => 'success',
            'message' => lexicon('msg_Navigation_OptionDeleted', 'Navigation option deleted successfully.')
        ]);
    }
}
