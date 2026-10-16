<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Permission Management.'));

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create(['name' => strtolower(trim($validated['name']))]);

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Roles_PermissionCreated', 'Permission created successfully!'),
        ]);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Permission Management.'));

        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Roles_PermissionDeleted', 'Permission deleted successfully!'),
        ]);
    }
}
