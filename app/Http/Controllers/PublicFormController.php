<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormField;
use App\Models\Application;
use App\Models\Page;
use Stevebauman\Purify\Facades\Purify;

class PublicFormController extends Controller
{
    public function show($type)
    {
        $fields = FormField::where('form_type', $type)->orderBy('order')->get();

        if ($fields->isEmpty()) {
            abort(404, lexicon('msg_FormsHub_FormNotFound', 'Form not found or has no active fields.'));
        }

        $slug = $type === 'application' ? 'apply' : $type;
        $systemPage = Page::with('layout.blocks')
            ->where('slug', $slug)
            ->where('is_published', 1)
            ->first();

        return view('form', compact('fields', 'type', 'systemPage'));
    }

    public function submit(Request $request, $identifier)
    {
        if ($identifier === 'application' || $identifier == 25) {
            if (!auth()->check()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => lexicon('msg_auth_RequiredForApp', 'Please log in or register an account before submitting an application.')
                    ], 401);
                }
                return back()->with('error', lexicon('msg_auth_RequiredForApp', 'Please log in or register an account before submitting an application.'));
            }

            $formData = $request->except(['_token']);
            $charName = trim($request->input('field_1') ?? '') ?: 'Unknown Character';

            $application = Application::create([
                'user_id'        => auth()->id(),
                'character_name' => $charName,
                'status'         => 'Pending',
                'submitted_at'   => now(),
            ]);

            $fieldsById = FormField::where('form_linked', '25')
                ->orWhere('form_type', 'application')
                ->get()
                ->keyBy('id');

            $allowedEvents = ['background', 'academy', 'assignment', 'commendation', 'milestone', 'incident'];

            foreach ($formData as $key => $value) {
                if (!str_starts_with($key, 'field_')) {
                    continue;
                }

                $fieldId = (int) str_replace('field_', '', $key);
                $fieldModel = $fieldsById->get($fieldId);

                if (!$fieldModel) {
                    continue;
                }

                $formattedAnswer = '';

                if ($fieldModel->type === 'service_record' && is_array($value)) {
                    $cleanRecords = [];
                    foreach ($value as $entry) {
                        $desc = trim(strip_tags($entry['description'] ?? ''));
                        if ($desc === '') {
                            continue;
                        }
                        $era = trim(strip_tags($entry['era'] ?? ''));
                        $eventType = in_array($entry['event_type'] ?? '', $allowedEvents, true)
                            ? $entry['event_type']
                            : 'background';

                        $cleanRecords[] = [
                            'era'         => $era,
                            'event_type'  => $eventType,
                            'description' => $desc,
                        ];
                    }
                    $formattedAnswer = json_encode($cleanRecords);
                } elseif ($fieldModel->type === 'richtext') {
                    $formattedAnswer = Purify::clean((string) $value);
                } else {
                    $formattedAnswer = is_array($value)
                        ? implode(', ', array_map('strip_tags', $value))
                        : trim(strip_tags((string) $value));
                }

                $application->answers()->create([
                    'form_field_id' => $fieldId,
                    'answer'        => $formattedAnswer,
                ]);
            }

            $message = lexicon('msg_FormsHub_ApplicationSuccess', 'Your character application has been submitted for review!');

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'success',
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return back()->with('status', $message);
        }

        abort(404);
    }
}
