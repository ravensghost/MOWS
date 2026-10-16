<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Page;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('manage_users'), 403, lexicon('msg_Users_Unauthorised', 'Unauthorised access to User Management.'));

        $systemPage = Page::where('slug', 'admin/users')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $users = User::with('roles')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users', compact('users', 'roles', 'systemPage'));
    }

    public function updateRole(Request $request, User $user)
    {
        abort_unless(auth()->user()->can('manage_users'), 403, lexicon('msg_Users_Unauthorised', 'Unauthorised access to User Management.'));

        $validated = $request->validate([
            'role' => 'required|string|max:255',
        ]);

        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => lexicon('msg_Users_CannotChangeOwnRole', 'You cannot change your own role.')
            ], 422);
        }

        $roleName = $validated['role'];

        // Maintain backwards compatibility with legacy enum column while syncing Spatie role
        $allowedEnumRoles = ['user', 'senior user', 'admin', 'webmaster'];
        $enumRole = in_array(strtolower($roleName), $allowedEnumRoles, true) ? strtolower($roleName) : 'user';

        $user->update(['role' => $enumRole]);

        if (Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
            $user->syncRoles([$roleName]);
        }

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Users_RoleUpdated', 'Role updated successfully.') . " ({$user->name})",
        ]);
    }

    public function updateLoa(Request $request, User $user)
    {
        abort_unless(auth()->user()->can('manage_users'), 403, lexicon('msg_Users_Unauthorised', 'Unauthorised access to User Management.'));

        $validated = $request->validate([
            'is_on_loa'       => 'required|boolean',
            'loa_return_date' => 'nullable|required_if:is_on_loa,1,true|date',
            'reason'          => 'nullable|string|max:255',
        ]);

        $isOnLoa = (bool) $validated['is_on_loa'];

        $statusLabel = $user->syncLoaStatus(
            $isOnLoa,
            $validated['loa_return_date'] ?? null,
            $validated['reason'] ?? lexicon('msg_Users_UpdatedByAdmin', 'Updated by Administrator'),
            auth()->id()
        );

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => "{$user->name}: {$statusLabel}.",
        ]);
    }
}
