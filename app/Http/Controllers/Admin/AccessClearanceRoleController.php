<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\TableOfContent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessClearanceRoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Role Management.'));

        $systemPage = Book::where('book_url', 'admin/access-clearance-roles')
            ->where('is_published', true)
            ->with([
                'grid.gridModules',
                'module' => function ($query) {$query->orderBy('module_sort_order');
                }
            ])
            ->firstOrFail();

        $roles = Role::with('permissions')->orderBy('name')->get();$allPermissions = Permission::orderBy('name')->get();

        $entries = TableOfContent::orderBy('display_location')
            ->orderBy('group_sort_order')
            ->orderBy('entry_sort_order')
            ->get();

        return view('admin.access_clearance_roles', [
            'systemPage'      => $systemPage,
            'roles'           => $roles,
            'allPermissions'  => $allPermissions,
            'entries'         => $entries,
            'navigationLinks' => $entries,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Role Management.'));

        $roleId =$request->input('role_id');

        $validated =$request->validate([
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

        $permissionIds =$validated['permissions'] ?? [];
        $permissionModels = Permission::whereIn('id',$permissionIds)->get();

        if ($roleId) {
            $role = Role::findOrFail($roleId);

            // Prevent renaming the core webmaster role while still allowing permission sync
            $newName = $role->name === 'webmaster' ? 'webmaster' : strtolower(trim($validated['name']));
            $role->update(['name' =>$newName]);
            $role->syncPermissions($permissionModels);

            $model =$role->load('permissions');

            return response()->json(['status' => 'success', 'message' => lexicon('msg_Roles_RoleUpdated', 'Role updated successfully!'), 'data' => $model, 'redirect' => null], 200);
        }

        $role = Role::create(['name' => strtolower(trim($validated['name']))]);
        $role->syncPermissions($permissionModels);

        $model =$role->load('permissions');

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Roles_RoleCreated', 'Role created successfully!'), 'data' => $model, 'redirect' => null], 200);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('manage_roles'), 403, lexicon('msg_Roles_Unauthorised', 'Unauthorised access to Role Management.'));

        $model = Role::findOrFail($id);

        if ($model->name === 'webmaster') {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_Roles_CannotDeleteWebmaster', 'Cannot delete the Webmaster role.'),
                'data'     => null,
                'redirect' => null,
            ], 403);
        }

        $model->delete();

        return response()->json(['status' => 'success', 'message' => lexicon('msg_Roles_RoleDeleted', 'Role deleted successfully!'), 'data' => $model, 'redirect' => null], 200);
    }
}