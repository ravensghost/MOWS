@extends('layouts.app')

@section('title', lexicon('admin_label_applications', 'Application & Roster Queue'))

@section('content')
<div x-data="applicationManager({
        rejectConfirm: '{{ addslashes(lexicon('admin_reject_confirm', 'Are you sure you want to reject this application?')) }}',
        confirmRejectBtn: '{{ addslashes(lexicon('btn_Applications_ConfirmReject', 'Yes, Reject')) }}',
        rejectFailed: '{{ addslashes(lexicon('msg_Applications_RejectFailed', 'Failed to reject application.')) }}'
    })"
    @ajax-success.window="refreshDOM()">

    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_applications', 'Application & Roster Queue')" />

            <!-- 1. PENDING APPLICATIONS ACCORDION -->
            <x-accordion-panel :title="lexicon('app_th_submitted', 'Pending Applications')" :expanded="true" class="mb-4">
                <div x-ref="pendingAppsTableContent">
                    <x-table>
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('app_th_submitted', 'Submitted') }}</th>
                            <th class="p-4 text-left">{{ lexicon('app_th_char_name', 'Character Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('app_th_applicant', 'Applicant') }}</th>
                            <th class="p-4 text-left">{{ lexicon('app_th_status', 'Status') }}</th>
                            <th class="p-4 text-right">{{ lexicon('app_th_actions', 'Actions') }}</th>
                        </x-slot:headers>
                        <tbody class="divide-y divide-panel">
                            @forelse($applications as $app)
                                @php
                                    $formattedAnswers =$app->answers
                                        ->sortBy(fn($a) =>$a->formField->order ?? 999)
                                        ->values()
                                        ->map(function ($a) {
                                            $type =$a->formField->type ?? 'text';
                                            $parsedRecords = [];
                                            if ($type === 'service_record' && !empty($a->answer)) {
                                                $decoded = json_decode($a->answer, true);
                                                if (is_array($decoded)) {
                                                    $parsedRecords =$decoded;
                                                }
                                            }
                                            return [
                                                'label'   => $a->formField->name ?? 'Field #' . $a->form_field_id,
                                                'type'    => $type,
                                                'answer'  => $a->answer ?? '',
                                                'records' => $parsedRecords,
                                            ];
                                        });
                                @endphp
                                <tr class="table-row">
                                    <td class="p-4 font-mono text-xs text-muted" x-tooltip="'{{ addslashes(lexicon('tooltip_Applications_SubmittedDate', 'Timestamp of submission')) }}'">
                                        {{ \Carbon\Carbon::parse($app->submitted_at)->diffForHumans() }}
                                    </td>
                                    <td class="p-4 font-bold text-main">{{ $app->character_name }}</td>
                                    <td class="p-4 text-sm text-muted">{{ $app->user->name ?? lexicon('label_common_Unknown', 'Unknown') }}</td>
                                    <td class="p-4">
                                        <x-badge variant="warning">{{ $app->status }}</x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <x-button variant="primary" size="sm"
                                            @click="openReviewModal({ 
                                                appId: {{ $app->id }}, 
                                                charName: '{{ addslashes($app->character_name) }}', 
                                                applicantName: '{{ addslashes($app->user->name ?? 'Unknown') }}', 
                                                submittedAt: '{{ \Carbon\Carbon::parse($app->submitted_at)->format('M d, Y - H:i') }}', 
                                                base64Answers: '{{ base64_encode(json_encode($formattedAnswers)) }}' 
                                            })"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Applications_Review', 'Review character application details')) }}'"
                                            aria-label="{{ lexicon('tooltip_Applications_Review', 'Review character application details') }}">
                                            {{ lexicon('btn_review_app', 'Review') }} &rarr;
                                        </x-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-muted italic">
                                        {{ lexicon('msg_app_no_pending_apps', 'No pending applications at this time.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- 2. ACTIVE CHARACTER MANAGEMENT ACCORDION -->
            <x-accordion-panel :title="lexicon('acm_active_roster', 'Active Character Management')">
                <div x-ref="activeRosterTableContent">
                    <x-table>
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('acm_th_char_name', 'Character Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('acm_th_current_position', 'Current Position') }}</th>
                            <th class="p-4 text-left">{{ lexicon('acm_th_status', 'Status') }}</th>
                            <th class="p-4 text-right">{{ lexicon('acm_th_actions', 'Actions') }}</th>
                        </x-slot:headers>
                        <tbody class="divide-y divide-panel">
                            @forelse($activeCharacters ?? [] as $char)
                                <tr class="table-row">
                                    <td class="p-4 font-bold text-main">{{ $char->name }}</td>
                                    <td class="p-4 text-sm text-muted">
                                        {{ $char->position ? $char->position->name : lexicon('msg_acm_no_position', 'Unassigned') }}
                                    </td>
                                    <td class="p-4">
                                        <x-badge variant="success">{{ ucfirst($char->status) }}</x-badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <x-button variant="text" class="text-warning hover-warning text-sm"
                                            @click="openReassignModal({ 
                                                charId: {{ $char->id }}, 
                                                charName: '{{ addslashes($char->name) }}', 
                                                currentPosId: '{{ $char->position_id ?? '' }}', 
                                                currentRankId: '{{ $char->rank_id ?? '' }}' 
                                            })"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_Applications_EditAssignment', 'Edit character rank and position assignment')) }}'"
                                            aria-label="{{ lexicon('tooltip_Applications_EditAssignment', 'Edit character assignment') }}">
                                            {{ lexicon('btn_reassign', 'Edit Assignment') }}
                                        </x-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-muted italic">
                                        {{ lexicon('msg_acm_no_active_chars', 'No active characters found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-table>
                </div>
            </x-accordion-panel>

            <!-- MODAL 1: APPLICATION REVIEW -->
            <x-modal show="reviewModalOpen" maxWidth="max-w-4xl">
                <x-slot:top>
                    <span>{{ lexicon('app_title', 'Application:') }} <span class="text-accent" x-text="charName"></span></span>
                </x-slot:top>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <!-- Biography & Service Record Submission (Left) -->
                    <div class="flex flex-col gap-sm">
                        <span class="font-bold text-main">{{ lexicon('app_bio_submission', 'Biography Submission') }}</span>
                        <div class="flex flex-col gap-sm">
                            <template x-if="hasParseError">
                                <span class="text-danger italic text-sm">{{ lexicon('msg_Applications_ParseError', 'Failed to parse application data.') }}</span>
                            </template>

                            <template x-for="(item, idx) in answers" :key="idx">
                                <div class="panel p-3 flex flex-col gap-xs">
                                    <span class="text-xs font-bold text-muted uppercase tracking-wider" x-text="item.label"></span>
                                    
                                    <!-- Service Record Timeline Preview -->
                                    <template x-if="item.type === 'service_record'">
                                        <div class="flex flex-col gap-xs">
                                            <template x-if="!item.records || item.records.length === 0">
                                                <span class="text-xs text-muted italic">{{ lexicon('msg_Applications_NoServiceRecords', 'No service record entries submitted.') }}</span>
                                            </template>
                                            <template x-for="(rec, rIdx) in item.records" :key="rIdx">
                                                <div class="panel-sub p-2 flex flex-row items-center gap-sm text-sm">
                                                    <x-badge variant="info" class="uppercase" x-text="rec.event_type"></x-badge>
                                                    <span class="font-mono text-xs text-accent" x-show="rec.era" x-text="`[${rec.era}]`"></span>
                                                    <span class="text-main" x-text="rec.description"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Rich Text Answer -->
                                    <template x-if="item.type === 'richtext'">
                                        <div class="text-main text-sm tiptap-content" x-html="window.DOMPurify.sanitize(item.answer)"></div>
                                    </template>

                                    <!-- Standard Text / Dropdown Answer -->
                                    <template x-if="item.type !== 'service_record' && item.type !== 'richtext'">
                                        <span class="text-main text-sm whitespace-pre-wrap" x-text="item.answer"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Council Controls (Right) -->
                    <div class="flex flex-col gap-md panel-sub p-4">
                        <span class="text-lg font-bold text-main border-b pb-2">{{ lexicon('app_applicant_info', 'Applicant Info') }}</span>
                        <ul class="text-sm flex flex-col gap-xs text-muted">
                            <li><strong class="text-main">{{ lexicon('app_label_applicant', 'Applicant:') }}</strong> <span x-text="applicantName"></span></li>
                            <li><strong class="text-main">{{ lexicon('app_label_submitted', 'Submitted:') }}</strong> <span x-text="submittedAt"></span></li>
                        </ul>

                        <span class="text-lg font-bold text-main border-b pb-2">{{ lexicon('app_council_decision', 'Council Decision') }}</span>

                        <!-- Approve Form -->
                        <form :action="`/admin/applications/${appId}/approve`" method="POST" class="flex flex-col gap-md panel p-3" x-data="ajaxForm" @submit.prevent="submit">
                            @csrf
                            <div class="flex flex-col gap-xs">
                                <label class="text-xs font-bold text-input-muted">{{ lexicon('app_label_assign_position', 'Assign Position') }}</label>
                                <select name="position_id" required class="form-input w-full" x-init="new window.TomSelect($el, { searchField: ['text'] })">
                                    <option value="">{{ lexicon('app_select_position', '-- Select Position --') }}</option>
                                    @foreach($sections ?? [] as $section)
                                        <optgroup label="{{ $section->name }}" class="panel text-accent">
                                            @foreach($section->positions as $pos)
                                                <option value="{{ $pos->id }}" class="text-main">{{ $pos->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="text-xs font-bold text-input-muted">{{ lexicon('app_label_assign_rank', 'Assign Rank') }}</label>
                                <select name="rank_id" required class="form-input w-full" x-init="new window.TomSelect($el, { searchField: ['text'] })">
                                    <option value="">{{ lexicon('app_select_rank', '-- Select Rank --') }}</option>
                                    @foreach($rankSets ?? [] as $set)
                                        <optgroup label="{{ $set->name }}" class="panel text-warning">
                                            @foreach($set->ranks as $rank)
                                                <option value="{{ $rank->id }}" class="text-main">{{ $rank->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <div class="border-t-actions pt-4 text-right">
                                <x-button variant="success" type="submit" class="w-full" x-tooltip="'{{ addslashes(lexicon('tooltip_Applications_ApproveSubmit', 'Approve application and integrate character into active roster')) }}'">
                                    {{ lexicon('btn_approve_char', 'Approve & Integrate') }}
                                </x-button>
                            </div>
                        </form>

                        <!-- Reject Button -->
                        <x-button variant="danger" type="button" class="w-full" @click="rejectApplication()" x-tooltip="'{{ addslashes(lexicon('tooltip_Applications_RejectSubmit', 'Reject application and notify applicant')) }}'">
                            {{ lexicon('btn_reject_app', 'Reject Application') }}
                        </x-button>
                    </div>
                </div>
            </x-modal>

            <!-- MODAL 2: CHARACTER REASSIGNMENT -->
            <x-modal show="reassignModalOpen" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('admin_edit_character', 'Edit:') }} <span class="text-accent" x-text="charName"></span></span>
                </x-slot:top>

                <form :action="`/admin/characters/${charId}/reassign`" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf
                                    
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">{{ lexicon('admin_label_assign_position', 'Assign Position') }}</label>
                        <select name="position_id" required class="form-input w-full"
                            x-init="
                                let ts = new window.TomSelect($el, { searchField: ['text'], onChange: (val) => { currentPosId = val; } });$watch('currentPosId', (val) => { ts.setValue(val, true); });
                            ">
                            <option value="">{{ lexicon('admin_select_position', '-- Select Position --') }}</option>
                            @foreach($sections ?? [] as $section)
                                <optgroup label="{{ $section->name }}" class="panel text-accent">
                                    @foreach($section->positions as $pos)
                                        <option value="{{ $pos->id }}" class="text-main">{{ $pos->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">{{ lexicon('admin_label_assign_rank', 'Assign Rank') }}</label>
                        <select name="rank_id" required class="form-input w-full"
                            x-init="
                                let ts = new window.TomSelect($el, { searchField: ['text'], onChange: (val) => { currentRankId = val; } });$watch('currentRankId', (val) => { ts.setValue(val, true); });
                            ">
                            <option value="">{{ lexicon('admin_select_rank', '-- Select Rank --') }}</option>
                            @foreach($rankSets ?? [] as $set)
                                <optgroup label="{{ $set->name }}" class="panel text-warning">
                                    @foreach($set->ranks as $rank)
                                        <option value="{{ $rank->id }}" class="text-main">{{ $rank->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                
                    <div class="border-t-actions pt-6 text-right">
                        <x-button variant="primary" type="submit" x-tooltip="'{{ addslashes(lexicon('tooltip_Applications_SaveAssignment', 'Save character assignment changes')) }}'">
                            {{ lexicon('btn_save_changes', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection