<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
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

        $users = User::with(['roles', 'attributes'])->orderBy('name')->get();
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
        abort_unless(
            auth()->user()->can('manage_users') || auth()->user()->can('access-admin'),
            403,
            lexicon('msg_Users_Unauthorised', 'Unauthorised access to User Management.')
        );

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

    /**
     * Send an administrative check-in ping to a player regarding inactivity or expired LOA.
     */
    public function pingPlayer(Request $request, User $user)
    {
        abort_unless(
            auth()->user()->can('manage_users') || auth()->user()->can('access-admin'),
            403,
            lexicon('msg_Users_Unauthorised', 'Unauthorised access to User Management.')
        );

        $validated = $request->validate([
            'context' => 'nullable|string|in:loa,inactivity',
            'note'    => 'nullable|string|max:500',
        ]);

        $context = $validated['context'] ?? 'inactivity';
        $customNote = trim((string) ($validated['note'] ?? ''));

        $subject = $context === 'loa'
            ? lexicon('msg_AdminDashboard_LoaPingSubject', 'Leave of Absence Status Check-In')
            : lexicon('msg_AdminDashboard_ActivityPingSubject', 'Station Activity Check-In');

        $defaultBody = $context === 'loa'
            ? "Hello {$user->name}, this is a friendly check-in from the Command Staff regarding your Leave of Absence status. When you are ready, please update your expected return date or return to Active Duty on your dashboard."
            : "Hello {$user->name}, this is a friendly check-in from the Command Staff! We noticed you haven't logged in recently and wanted to check on your availability.";

        $body = $customNote !== '' ? "{$defaultBody}\n\nNote from Admin: {$customNote}" : $defaultBody;

        $channelsDispatched = [];

        if (NotificationService::sendPm(auth()->user(), [$user], $body, $subject)) {
            $channelsDispatched[] = 'PM';
        }

        if (! empty($user->email) && NotificationService::sendEmail($user->email, $subject, 'emails.notification', [
            'title'   => $subject,
            'content' => $body,
            'url'     => url('/dashboard'),
        ])) {
            $channelsDispatched[] = 'Email';
        }

        $discordMention = $user->discord_id ? "<@{$user->discord_id}> " : "**{$user->name}**: ";
        if (NotificationService::sendDiscord('alerts', "🔔 **{$subject}** — {$discordMention}{$body}")) {
            $channelsDispatched[] = 'Discord';
        }

        $channelSummary = ! empty($channelsDispatched)
            ? ' (' . implode(', ', $channelsDispatched) . ')'
            : ' (All notification channels currently disabled in Global Settings)';

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => "Check-in ping processed for {$user->name}{$channelSummary}.",
        ]);
    }
}
