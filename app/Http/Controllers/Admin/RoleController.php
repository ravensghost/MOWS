<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Page;
use App\Models\NavigationLink;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Role Management.'));

        $systemPage = Page::where('slug', 'admin/roles')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $roles = Role::with('permissions')->orderBy('name')->get();
        $allPermissions = Permission::orderBy('name')->get();

        // Fetch all navigation links for the permission audit view panel
        $navigationLinks = NavigationLink::orderBy('location')
            ->orderBy('group_order')
            ->orderBy('order')
            ->get();

        return view('admin.roles', compact('systemPage', 'roles', 'allPermissions', 'navigationLinks'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Role Management.'));

        $roleId = $request->input('role_id');

        $validated = $request->validate([
            'role_id'       => 'nullable|exists:roles,id',
            'name'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissionIds = $validated['permissions'] ?? [];
        $permissionModels = Permission::whereIn('id', $permissionIds)->get();

        if ($roleId) {
            $role = Role::findOrFail($roleId);

            // Prevent renaming the core webmaster role while still allowing permission sync
            $newName = $role->name === 'webmaster' ? 'webmaster' : strtolower(trim($validated['name']));
            $role->update(['name' => $newName]);
            $role->syncPermissions($permissionModels);

            return response()->json([
                'status'  => 'success',
                'success' => true,
                'message' => lexicon('msg_Roles_RoleUpdated', 'Role updated successfully!'),
            ]);
        }

        $role = Role::create(['name' => strtolower(trim($validated['name']))]);
        $role->syncPermissions($permissionModels);

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Roles_RoleCreated', 'Role created successfully!'),
        ]);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Role Management.'));

        $role = Role::findOrFail($id);

        if ($role->name === 'webmaster') {
            return response()->json([
                'status'  => 'error',
                'message' => lexicon('msg_Roles_CannotDeleteWebmaster', 'Cannot delete the Webmaster role.'),
            ], 403);
        }

        $role->delete();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Roles_RoleDeleted', 'Role deleted successfully!'),
        ]);
    }
}
