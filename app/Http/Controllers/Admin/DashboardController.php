<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Character;
use App\Models\Page;
use App\Models\Position;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        abort_unless(
            auth()->user()?->can('access-admin'),
            403,
            lexicon('msg_AdminDashboard_Unauthorised', 'Unauthorised access to the Command Centre.')
        );

        $systemPage = Page::where('slug', 'admin')
            ->where('is_published', true)
            ->with([
                'layout.blocks',
                'widgets' => function ($query) {
                    $query->orderBy('page_widget.sort_order');
                }
            ])
            ->firstOrFail();

        $positions = Position::where('is_open', true)
            ->withCount(['characters' => function ($query) {
                $query->where('status', 'active');
            }])->get();

        $trueOpenSlots = $positions->sum(function ($position) {
            return max(0, $position->capacity - $position->characters_count);
        });

        return view('admin.dashboard', [
            'systemPage' => $systemPage,
            'pendingApps' => Application::where('status', 'Pending')->count(),
            'activeUsers' => User::count(),
            'totalCharacters' => Character::where('status', 'active')->count(),
            'openPositions' => $trueOpenSlots,
        ]);
    }
}
