<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Book;
use App\Models\ChronologicalRegister;
use App\Models\Distinction;
use App\Models\Insignia;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChronologicalRegisterController extends Controller
{
    public function index()
    {
        $systemBook = Book::with(['grid.gridModules', 'modules'])
            ->where('book_url', 'admin/service-records')
            ->where('is_published', true)
            ->firstOrFail();

        $awards = Distinction::with('catalogue')->orderBy('distinction_title')->get();
        $users = User::orderBy('name')->get();
        $characters = Persona::with(['user', 'insignia', 'assignment'])->orderBy('persona_name')->get();

        $ranks = Insignia::orderBy('insignia_sort_order', 'desc')->get();
        $positions = Assignment::with('division')->orderBy('assignment_title')->get();
        $eventTypeGroups = ChronologicalRegister::eventTypeGroups();

        $recentRecords = ChronologicalRegister::select('chronological_registers.*')
            ->with(['distinction', 'user', 'persona.user', 'authorisingUser'])
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->get();

        return view('admin.chronological_registers', compact(
            'systemBook',
            'awards',
            'users',
            'characters',
            'recentRecords',
            'ranks',
            'positions',
            'eventTypeGroups'
        ));
    }

    public function issueAward(Request $request)
    {
        $validated = $request->validate([
            'distinction_id' => 'required|exists:distinctions,id',
            'chronicle_text' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
            'persona_id' => 'nullable|exists:personae,id',
        ]);

        if (empty($validated['user_id']) && empty($validated['persona_id'])) {
            return response()->json(['message' => 'You must select a user or persona to receive this distinction.'], 422);
        }

        if (! empty($validated['user_id']) && $validated['user_id'] == auth()->id()) {
            return response()->json(['message' => 'You cannot issue a distinction to your own user account.'], 422);
        }

        $targetUserId = $validated['user_id'] ?? null;

        if (! empty($validated['persona_id'])) {
            $persona = Persona::findOrFail($validated['persona_id']);
            if ($persona->user_id == auth()->id()) {
                return response()->json(['message' => 'You cannot issue a distinction to your own persona.'], 422);
            }
            $targetUserId = $persona->user_id;
        }

        $distinction = Distinction::findOrFail($validated['distinction_id']);

        if ($distinction->scope_target === 'IC' && ! empty($validated['user_id']) && empty($validated['persona_id'])) {
            return response()->json(['message' => 'This is an In-Character distinction and cannot be given directly to a User.'], 422);
        }
        if ($distinction->scope_target === 'OOC' && ! empty($validated['persona_id'])) {
            return response()->json(['message' => 'This is an Out-Of-Character distinction and cannot be given to a Persona.'], 422);
        }

        $record = ChronologicalRegister::create([
            'user_id' => $targetUserId,
            'persona_id' => $validated['persona_id'] ?? null,
            'distinction_id' => $distinction->id,
            'register_event_mode' => 'award',
            'chronicle_text' => $validated['chronicle_text'],
            'authorising_user_id' => auth()->id(),
            'scope_target' => $distinction->scope_target,
        ]);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_distinction_issued', 'Distinction issued and added to chronological register!'), 'data' => $record, 'redirect' => null], 200);
    }

    public function removeAward(Request $request, ChronologicalRegister $chronologicalRegister)
    {
        $validated = $request->validate([
            'chronicle_text' => 'required|string',
        ]);

        $record = ChronologicalRegister::create([
            'user_id' => $chronologicalRegister->user_id,
            'persona_id' => $chronologicalRegister->persona_id,
            'distinction_id' => $chronologicalRegister->distinction_id,
            'register_event_mode' => $chronologicalRegister->register_event_mode,
            'chronicle_text' => 'REVOKED: '.$validated['chronicle_text'],
            'authorising_user_id' => auth()->id(),
            'scope_target' => $chronologicalRegister->scope_target ?? 'BOTH',
        ]);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_register_revoked', 'Action revoked and logged in chronological register.'), 'data' => $record, 'redirect' => null], 200);
    }

    public function updateRecord(Request $request, ChronologicalRegister $chronologicalRegister)
    {
        $validated = $request->validate([
            'chronicle_text' => 'required|string',
            'scope_target' => 'required|in:IC,OOC,BOTH',
            'register_event_mode' => ['nullable', 'string', Rule::in(ChronologicalRegister::allEventTypes())],
        ]);

        $updateData = [
            'chronicle_text' => $validated['chronicle_text'],
            'scope_target' => $validated['scope_target'],
        ];

        if (! empty($validated['register_event_mode']) && ! $chronologicalRegister->distinction_id) {
            $updateData['register_event_mode'] = $validated['register_event_mode'];
        }

        $chronologicalRegister->update($updateData);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_register_updated', 'Chronological register updated successfully.'), 'data' => $chronologicalRegister, 'redirect' => null], 200);
    }

    public function issueManualEntry(Request $request)
    {
        $validated = $request->validate([
            'persona_id' => 'required|exists:personae,id',
            'register_event_mode' => ['nullable', 'string', Rule::in(ChronologicalRegister::allEventTypes())],
            'scope_target' => 'nullable|in:IC,OOC,BOTH',
            'chronicle_text' => 'required|string',
        ]);

        $persona = Persona::findOrFail($validated['persona_id']);

        $record = ChronologicalRegister::create([
            'user_id' => $persona->user_id,
            'persona_id' => $persona->id,
            'register_event_mode' => $validated['register_event_mode'] ?? 'manual_entry',
            'chronicle_text' => $validated['chronicle_text'],
            'authorising_user_id' => auth()->id(),
            'scope_target' => $validated['scope_target'] ?? 'BOTH',
        ]);

        return response()->json(['status' => 'success', 'message' => lexicon('msg_register_entry_added', 'Chronological register entry added.'), 'data' => $record, 'redirect' => null], 200);
    }
}
