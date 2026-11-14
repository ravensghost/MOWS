<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class AccessClearancePermissionController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Permission Management.'));

        $validated =$request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $model = Permission::create(['name' => strtolower(trim($validated['name']))]);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Roles_PermissionCreated', 'Permission created successfully!'), 'data' => $model, 'redirect' => null], 200);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Permission Management.'));

        $model = Permission::findOrFail($id);$model->delete();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Roles_PermissionDeleted', 'Permission deleted successfully!'), 'data' => $model, 'redirect' => null], 200);
    }
}