<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CandidatePetition;
use App\Models\Configuration;
use App\Models\Metadata;
use App\Models\Persona;
use Illuminate\Http\Request;
use Stevebauman\Purify\Facades\Purify;

class CandidatePetitionSubmissionController extends Controller
{
    /**
     * Display the public petition submission form.
     */
    public function show($metaMode)
    {
        $fields = Metadata::where('meta_mode',$metaMode)
            ->orderBy('metadata_sort_order')
            ->get();

        if ($fields->isEmpty()) {
            abort(404, lexicon('msg_FormsHub_FormNotFound', 'Form not found or has no active fields.'));
        }

        $slug = $metaMode === 'application' ? 'apply' :$metaMode;
        $systemPage = Book::with('layout.blocks')
            ->where('slug', $slug)
            ->where('is_published', 1)
            ->first();

        return view('petition_metadata', [
            'fields'     => $fields,
            'metaMode'   => $metaMode,
            'systemPage' => $systemPage,
        ]);
    }

    /**
     * Store a newly submitted candidate petition (alias for submit).
     */
    public function store(Request $request,$identifier = 'application')
    {
        return $this->submit($request,$identifier);
    }

    /**
     * Handle AJAX submission of a candidate petition.
     */
    public function submit(Request $request,$identifier)
    {
        if ($identifier === 'application' || (int)$identifier === 25) {
            if (! auth()->check()) {
                $authMessage = lexicon('msg_auth_RequiredForApp', 'Please log in or register an account before submitting a petition.');
                if ($request->wantsJson()) {
                    return response()->json([
                        'status'   => 'error',
                        'message'  => $authMessage,
                        'data'     => null,
                        'redirect' => null,
                    ], 401);
                }
                return back()->with('error', $authMessage);
            }

            // Check configuration token: character_limit_enforced
            $limitEnforcedRaw = Configuration::where('configuration_token', 'character_limit_enforced')
                ->value('configuration_payload');

            $isLimitEnforced = filter_var($limitEnforcedRaw ?? false, FILTER_VALIDATE_BOOLEAN)
                || in_array((string) $limitEnforcedRaw, ['1', 'true', 'yes', 'enabled'], true);

            if ($isLimitEnforced) {$maxCharacters = max(1, (int) (Configuration::where('configuration_token', 'max_active_characters')
                    ->value('configuration_payload') ?? 3));

                $activePersonaCount = Persona::where('user_id', auth()->id())
                    ->whereIn('persona_status', ['active', 'loa', 'eloa'])
                    ->count();

                $pendingPetitionCount = CandidatePetition::where('user_id', auth()->id())
                    ->where('petition_status', 'Pending')
                    ->count();

                if (($activePersonaCount +$pendingPetitionCount) >= $maxCharacters) {$limitMsg = lexicon(
                        'msg_FormsHub_CharacterLimitReached',
                        'You have reached the maximum allowed number of active personae and pending petitions.'
                    );

                    if ($request->wantsJson()) {
                        return response()->json([
                            'status'   => 'error',
                            'message'  => $limitMsg,
                            'data'     => null,
                            'redirect' => null,
                        ], 422);
                    }
                    return back()->with('error', $limitMsg);
                }
            }

            $formData =$request->except(['_token']);
            $proposedPersonaName = trim($request->input('field_1') ?? '') ?: 'Unknown Persona';

            $petition = CandidatePetition::create([
                'user_id'               => auth()->id(),
                'proposed_persona_name' => $proposedPersonaName,
                'petition_status'       => 'Pending',
                'lodged_at'             => now(),
            ]);

            $fieldsById = Metadata::where('linked_metadata', '25')
                ->orWhere('meta_mode', 'application')
                ->get()
                ->keyBy('id');

            $allowedEvents = ['background', 'academy', 'assignment', 'commendation', 'milestone', 'incident'];

            foreach ($formData as $key =>$value) {
                if (! str_starts_with($key, 'field_')) {
                    continue;
                }

                $fieldId = (int) str_replace('field_', '', $key);$fieldModel = $fieldsById->get($fieldId);

                if (! $fieldModel) {
                    continue;
                }

                $fieldMode =$fieldModel->metadata_mode ?: 'text';
                $formattedAnswer = '';

                if ($fieldMode === 'service_record' && is_array($value)) {$cleanRecords = [];
                    foreach ($value as$entry) {
                        $desc = trim(strip_tags($entry['chronicle_text'] ?? ''));
                        if ($desc === '') { continue; }$era = trim(strip_tags($entry['era'] ?? ''));$eventCategory = in_array($entry['register_event_mode'] ?? '',$allowedEvents, true)
                            ? $entry['register_event_mode']
                            : 'background';

                        $cleanRecords[] = [
                            'era'                 => $era,
                            'register_event_mode' => $eventCategory,
                            'chronicle_text'      => $desc,
                        ];
                    }
                    $formattedAnswer = json_encode($cleanRecords);
                } elseif ($fieldMode === 'richtext') {
                    $formattedAnswer = Purify::clean((string)$value);
                } else {
                    $formattedAnswer = is_array($value)
                        ? implode(', ', array_map('strip_tags', $value))
                        : trim(strip_tags((string) $value));
                }

                $petition->entries()->create([
                    'metadata_id'     => $fieldId,
                    'petition_answer' => $formattedAnswer,
                ]);
            }

            $petition->load('entries');

            if ($request->wantsJson()) {
                return response()->json([
                    'status'   => 'success',
                    'message'  => lexicon('msg_FormsHub_ApplicationSuccess', 'Your candidate petition has been lodged for review!'),
                    'data'     => $petition,
                    'redirect' => null,
                ], 200);
            }

            return back()->with('notice', lexicon('msg_FormsHub_ApplicationSuccess', 'Your candidate petition has been lodged for review!'));
        }

        abort(404);
    }
}