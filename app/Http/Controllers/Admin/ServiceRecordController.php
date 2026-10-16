<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRecord;
use App\Models\Award;
use App\Models\User;
use App\Models\Character;
use App\Models\Page;
use App\Models\Ranks;
use App\Models\Position;

class ServiceRecordController extends Controller
{
    public function index()
    {
        // Eager load layout.blocks and widgets to keep queries under Q < 10
        $systemPage = Page::with(['layout.blocks', 'widgets'])
            ->where('slug', 'admin/service-records')
            ->where('is_published', true)
            ->firstOrFail();

        $awards = Award::with('category')->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $characters = Character::with(['user', 'rank', 'position.section'])->orderBy('name')->get();

        $ranks = Ranks::orderBy('sort_order', 'desc')->get();
        $positions = Position::with('section')->orderBy('name')->get();

        $recentRecords = ServiceRecord::select('service_records.*')
            ->with(['award', 'user', 'character.user', 'issuer'])
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->get();

        return view('admin.service-records', compact(
            'systemPage',
            'awards',
            'users',
            'characters',
            'recentRecords',
            'ranks',
            'positions'
        ));
    }

    public function issueAward(Request $request)
    {
        $request->validate([
            'award_id' => 'required|exists:awards,id',
            'reason' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
            'character_id' => 'nullable|exists:characters,id',
        ]);

        if (!$request->user_id && !$request->character_id) {
            return response()->json(['message' => 'You must select a user or character to receive this award.'], 422);
        }

        if ($request->user_id && $request->user_id == auth()->id()) {
            return response()->json(['message' => 'You cannot issue an award to your own user account.'], 422);
        }

        $targetUserId = $request->user_id;

        if ($request->character_id) {
            $character = Character::findOrFail($request->character_id);
            if ($character->user_id == auth()->id()) {
                return response()->json(['message' => 'You cannot issue an award to your own character.'], 422);
            }
            $targetUserId = $character->user_id;
        }

        $award = Award::findOrFail($request->award_id);

        if ($award->master_group === 'IC' && $request->user_id && !$request->character_id) {
            return response()->json(['message' => 'This is an In-Character award and cannot be given directly to a User.'], 422);
        }
        if ($award->master_group === 'OOC' && $request->character_id) {
            return response()->json(['message' => 'This is an Out-Of-Character award and cannot be given to a Character.'], 422);
        }

        ServiceRecord::create([
            'user_id' => $targetUserId,
            'character_id' => $request->character_id,
            'award_id' => $award->id,
            'event_type' => 'award',
            'description' => $request->reason,
            'issued_by' => auth()->id(),
            'display_target' => $award->master_group,
        ]);

        return response()->json(['success' => true, 'message' => 'Award issued and added to service record!']);
    }

    public function removeAward(Request $request, ServiceRecord $serviceRecord)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        ServiceRecord::create([
            'user_id' => $serviceRecord->user_id,
            'character_id' => $serviceRecord->character_id,
            'award_id' => $serviceRecord->award_id,
            'event_type' => $serviceRecord->event_type,
            'description' => 'REVOKED: ' . $request->reason,
            'issued_by' => auth()->id(),
            'display_target' => $serviceRecord->display_target ?? 'BOTH',
        ]);

        return response()->json(['success' => true, 'message' => 'Action revoked and logged in service record.']);
    }

    public function updateRecord(Request $request, ServiceRecord $serviceRecord)
    {
        $request->validate([
            'description' => 'required|string',
            'display_target' => 'required|in:IC,OOC,BOTH',
        ]);

        $serviceRecord->update([
            'description' => $request->description,
            'display_target' => $request->display_target,
        ]);

        return response()->json(['success' => true, 'message' => 'Service record updated successfully.']);
    }

    public function issueManualEntry(Request $request)
    {
        $validated = $request->validate([
            'character_id' => 'required|exists:characters,id',
            'description' => 'required|string',
        ]);

        $character = Character::findOrFail($validated['character_id']);

        ServiceRecord::create([
            'user_id'        => $character->user_id,
            'character_id'   => $character->id,
            'event_type'     => 'manual_entry',
            'description'    => $validated['description'],
            'issued_by'      => auth()->id(),
            'display_target' => 'BOTH',
        ]);

        return response()->json(['success' => true, 'message' => 'Service record entry added.']);
    }
}