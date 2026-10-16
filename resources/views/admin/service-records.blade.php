@extends('layouts.app')

@section('title', lexicon('admin_label_ServiceRecords', 'Service Records Hub'))

@section('content')
<div x-data="serviceRecordManager({{ json_encode($awards) }})"
     @ajax-success.window="window.updateDOM_US($refs, ['serviceRecordsTableContent'], () => { issueModalOpen = false; })">
    
    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <script type="application/json" x-ref="serviceRecordsSearchData">
                {!! json_encode($recentRecords->map(fn($r) => [
                    'id' => $r->id,
                    'recipient' => $r->character_id ? ($r->character->name ?? '') : ($r->user->name ?? ''),
                    'event_type' => $r->event_type,
                    'award_name' => optional($r->award)->name ?? '',
                    'description' => $r->description ?? '',
                    'target' => $r->display_target ?? 'BOTH'
                ])->values()) !!}
            </script>

            <x-page-header :title="lexicon('admin_label_ServiceRecords', 'Service Records Hub')">
                <x-slot:actions>
                    <x-button type="button" 
                              variant="success" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openIssueModal('award')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_IssueAward', 'Issue an IC or OOC award')) }}'"
                              aria-label="{{ lexicon('tooltip_ServiceRecords_IssueAward', 'Issue an IC or OOC award') }}">
                        <i class="bi bi-award"></i>
                        <span>{{ lexicon('btn_ServiceRecords_IssueAward', 'Issue Award') }}</span>
                    </x-button>
                    <x-button type="button" 
                              variant="primary" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openIssueModal('promotion')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_RankChange', 'Promote or demote a character')) }}'"
                              aria-label="{{ lexicon('tooltip_ServiceRecords_RankChange', 'Promote or demote a character') }}">
                        <i class="bi bi-person-badge"></i>
                        <span>{{ lexicon('btn_ServiceRecords_RankChange', 'Rank Change') }}</span>
                    </x-button>
                    <x-button type="button" 
                              variant="primary" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openIssueModal('transfer')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_Transfer', 'Transfer a character to a new position')) }}'"
                              aria-label="{{ lexicon('tooltip_ServiceRecords_Transfer', 'Transfer a character to a new position') }}">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>{{ lexicon('btn_ServiceRecords_Transfer', 'Transfer') }}</span>
                    </x-button>
                    <x-button type="button" 
                              variant="primary" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openIssueModal('status_change')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_StatusChange', 'Change character duty status')) }}'"
                              aria-label="{{ lexicon('tooltip_ServiceRecords_StatusChange', 'Change character duty status') }}">
                        <i class="bi bi-shield-check"></i>
                        <span>{{ lexicon('btn_ServiceRecords_StatusChange', 'Status Change') }}</span>
                    </x-button>
                    <x-button type="button" 
                              variant="primary" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openIssueModal('manual_entry')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_ManualNote', 'Log a manual service record entry')) }}'"
                              aria-label="{{ lexicon('tooltip_ServiceRecords_ManualNote', 'Log a manual service record entry') }}">
                        <i class="bi bi-journal-text"></i>
                        <span>{{ lexicon('btn_ServiceRecords_ManualNote', 'Manual Note') }}</span>
                    </x-button>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:middle>
            <div class="flex flex-col gap-lg">
                {{-- Instant Fuzzy Search Bar (Fuse.js) --}}
                <div class="bg-panel border border-panel rounded-panel shadow-panel p-panel">
                    <x-form-input name="service_records_search"
                                  id="service-records-search-input"
                                  :label="lexicon('label_ServiceRecords_FilterRecords', 'Filter Service Records')"
                                  :placeholder="lexicon('label_ServiceRecords_FilterPlaceholder', 'Search records by recipient, award, or citation...')"
                                  :tooltip="lexicon('tooltip_ServiceRecords_FilterInput', 'Instantaneously filter service records using fuzzy search')"
                                  xModel="searchQuery"
                                  @input="filterRecords()" />
                </div>
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- 1. RECENT SERVICE RECORDS ACCORDION PANEL --}}
            <x-accordion-panel :title="lexicon('label_ServiceRecords_RecentRecords', 'Recent Service Records')" :expanded="true">
                <x-slot:top>
                    <x-button type="button" 
                              variant="success" 
                              class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                              @click="openIssueModal('award')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_NewEntry', 'Create a new service record entry')) }}'"
                              aria-label="{{ lexicon('tooltip_ServiceRecords_NewEntry', 'Create a new service record entry') }}">
                        <i class="bi bi-plus-lg"></i>
                        <span>{{ lexicon('btn_ServiceRecords_NewEntry', 'New Entry') }}</span>
                    </x-button>
                </x-slot:top>

                <x-table id="service-records-table-content" x-ref="serviceRecordsTableContent" class="max-h-table" :bareBody="true">
                    <x-slot:headers>
                        <th class="p-4 text-left">{{ lexicon('label_common_Date', 'Date') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_ServiceRecords_Recipient', 'Recipient') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_ServiceRecords_IcOoc', 'IC/OOC') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_ServiceRecords_GivenTaken', 'Given/Taken') }}</th>
                        <th class="p-4 text-left">{{ lexicon('label_ServiceRecords_ReasonCitation', 'Reason / Citation') }}</th>
                        <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                    </x-slot:headers>

                    <tbody class="divide-y divide-panel">
                        @forelse($recentRecords as $record)
                            <tr class="border-b-table-row hover-bg-table-row" 
                                x-data="{ editing: false, editReason: '{{ addslashes($record->description) }}', editTarget: '{{ $record->display_target ?? 'BOTH' }}', removing: false }"
                                x-show="matchesRecord({{ $record->id }})"
                                x-cloak>
                                <td class="p-4 text-xs text-muted whitespace-nowrap">{{ function_exists('world_date') ? world_date($record->created_at) : $record->created_at->format('Y-m-d') }}</td>
                                <td class="p-4 font-bold text-p">
                                    @if($record->character_id)
                                        {{ $record->character->name ?? lexicon('label_ServiceRecords_UnknownCharacter', 'Unknown Character') }}[cite: 21]
                                    @else
                                        {{ $record->user->name ?? lexicon('label_ServiceRecords_UnknownUser', 'Unknown User') }}[cite: 21]
                                    @endif
                                </td>
                                <td class="p-4">
                                    <x-badge :variant="($record->display_target ?? 'OOC') === 'BOTH' ? 'success' : 'warning'"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_DisplayTargetBadge', 'Display target context scope')) }}'"
                                             aria-label="{{ lexicon('tooltip_ServiceRecords_DisplayTargetBadge', 'Display target context scope') }}">
                                        {{ $record->display_target ?? 'OOC' }}[cite: 21]
                                    </x-badge>
                                </td>
                                <td class="p-4 text-sm">
                                    @php
                                        $badgeVariant = match($record->event_type) {
                                            'promotion'     => 'success',
                                            'transfer'      => 'primary',
                                            'award'         => 'warning',
                                            'status_change' => 'info',
                                            'manual_entry'  => 'completed',
                                            default         => 'primary',
                                        };
                                        $isRevoked = \Illuminate\Support\Str::startsWith($record->description, 'REVOKED:');
                                    @endphp
                                
                                    <div class="flex flex-row flex-wrap gap-xs items-center">
                                        @if($isRevoked)
                                            <x-badge variant="danger" 
                                                     x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_Revoked', 'This record has been revoked')) }}'"
                                                     aria-label="{{ lexicon('tooltip_ServiceRecords_Revoked', 'This record has been revoked') }}">
                                                {{ lexicon('label_ServiceRecords_Revoked', 'Revoked') }}[cite: 21]
                                            </x-badge>
                                        @endif
                                    
                                        @if($record->award)
                                            <x-badge :variant="$badgeVariant"
                                                     x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry')) }}'"
                                                     aria-label="{{ lexicon('tooltip_ServiceRecords_AwardBadge', 'Award record entry') }}">
                                                {{ $record->award->name }}[cite: 21]
                                            </x-badge>
                                        @else
                                            <x-badge :variant="$badgeVariant"
                                                     x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry')) }}'"
                                                     aria-label="{{ lexicon('tooltip_ServiceRecords_EventBadge', 'Career event record entry') }}">
                                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $record->event_type)) }}[cite: 21]
                                            </x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 text-xs text-muted">
                                    <span x-show="!editing">
                                        {{ $record->description }}[cite: 21]
                                        @if($record->issuer)
                                            <span class="inline-block text-xs opacity-75 ml-1">
                                                ({{ lexicon('label_ServiceRecords_AuthorisedBy', 'Authorised by:') }} {{ $record->issuer->name }})[cite: 21]
                                            </span>
                                        @endif
                                    </span>
                                    
                                    {{-- Inline Edit Form --}}
                                    <form x-cloak 
                                          x-show="editing" 
                                          action="/admin/service-records/{{ $record->id }}" 
                                          method="POST" 
                                          x-data="ajaxForm" 
                                          @submit.prevent="submit; editing = false" 
                                          class="flex flex-row flex-wrap gap-xs items-center">
                                        @csrf
                                        @method('PUT')
                                        
                                        @if(optional($record->award)->master_group === 'BOTH')
                                            <select name="display_target" 
                                                    x-model="editTarget" 
                                                    aria-label="{{ lexicon('label_ServiceRecords_IcOoc', 'IC/OOC') }}"
                                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_SelectTargetScope', 'Select display target scope')) }}'"
                                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-24 box-border focus-border-accent focus-shadow-accent text-xs" 
                                                    required>
                                                <option value="BOTH">{{ lexicon('label_ServiceRecords_OptBoth', 'BOTH') }}</option>
                                                <option value="IC">{{ lexicon('label_ServiceRecords_OptIcOnly', 'IC Only') }}</option>
                                                <option value="OOC">{{ lexicon('label_ServiceRecords_OptOocOnly', 'OOC Only') }}</option>
                                            </select>
                                        @else
                                            <input type="hidden" name="display_target" value="{{ $record->display_target ?? 'BOTH' }}">
                                        @endif
                            
                                        <input type="text" 
                                               name="description" 
                                               x-model="editReason" 
                                               aria-label="{{ lexicon('label_ServiceRecords_ReasonCitation', 'Reason / Citation') }}"
                                               x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_EditCitationInput', 'Modify citation text')) }}'"
                                               class="bg-input text-input border-input rounded-input p-input outline-none h-input flex-grow box-border focus-border-accent focus-shadow-accent text-xs" 
                                               required>
                                        <x-button type="submit" variant="success" class="p-btn-sm text-xs">{{ lexicon('btn_common_Save', 'Save Changes') }}</x-button>
                                        <x-button type="button" variant="secondary" class="p-btn-sm text-xs" @click="editing = false">{{ lexicon('btn_common_Cancel', 'Cancel') }}</x-button>
                                    </form>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap items-center justify-end gap-sm text-sm" x-show="!removing">
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-text-h flex flex-row items-center gap-xs" 
                                                  @click="editing = true" 
                                                  x-show="!editing"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_EditRecord', 'Edit this record citation')) }}'"
                                                  aria-label="{{ lexicon('tooltip_ServiceRecords_EditRecord', 'Edit this record citation') }}">
                                            <i class="bi bi-pencil"></i>
                                            <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                        </x-button>
                                        @if(!$isRevoked)
                                            <x-button type="button" 
                                                      variant="text" 
                                                      class="text-danger hover-text-h flex flex-row items-center gap-xs" 
                                                      @click="removing = true"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_RemoveRecord', 'Revoke this service record')) }}'"
                                                      aria-label="{{ lexicon('tooltip_ServiceRecords_RemoveRecord', 'Revoke this service record') }}">
                                                <i class="bi bi-trash"></i>
                                                <span>{{ lexicon('btn_ServiceRecords_Remove', 'Remove') }}</span>
                                            </x-button>
                                        @endif
                                    </div>
                                    
                                    {{-- Remove Confirmation Box --}}
                                    <div x-show="removing" class="bg-panel-sub border border-panel rounded-panel p-2 mt-1" x-cloak>
                                        <form action="/admin/service-records/{{ $record->id }}/remove" 
                                              method="POST" 
                                              x-data="ajaxForm" 
                                              @submit.prevent="submit; removing = false" 
                                              class="flex flex-col gap-xs">
                                            @csrf
                                            <input type="text" 
                                                   name="reason" 
                                                   aria-label="{{ lexicon('label_ServiceRecords_RemoveReasonPlaceholder', 'Reason for removal...') }}"
                                                   x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_RemovalReasonInput', 'Enter mandatory reason for revoking this record')) }}'"
                                                   class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent text-xs" 
                                                   placeholder="{{ lexicon('label_ServiceRecords_RemoveReasonPlaceholder', 'Reason for removal...') }}" 
                                                   required>
                                            <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                                <x-button type="submit" 
                                                          variant="danger" 
                                                          class="p-btn-sm text-xs"
                                                          x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_ConfirmRemoval', 'Confirm revocation of this record')) }}'"
                                                          aria-label="{{ lexicon('tooltip_ServiceRecords_ConfirmRemoval', 'Confirm revocation of this record') }}">
                                                    {{ lexicon('btn_ServiceRecords_ConfirmRemoval', 'Confirm Removal') }}
                                                </x-button>
                                                <x-button type="button" variant="secondary" class="p-btn-sm text-xs" @click="removing = false">
                                                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                                                </x-button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-muted italic">{{ lexicon('msg_ServiceRecords_NoRecords', 'No service records found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-table>
            </x-accordion-panel>

            {{-- 2. MODAL: ISSUE SERVICE RECORD / AWARD --}}
            <x-modal show="issueModalOpen" maxWidth="max-w-lg">
                <x-slot:top>
                    <span>{{ lexicon('label_ServiceRecords_IssueRecordModalTitle', 'Issue Service Record') }}</span>
                </x-slot:top>

                {{-- Form Toggle Tabs --}}
                <div class="flex flex-row flex-wrap gap-xs mb-4 border-b border-panel pb-3">
                    <button type="button" 
                            class="font-bold rounded-input border transition-btn cursor-pointer p-btn-sm text-sm flex-grow" 
                            :class="actionTab === 'award' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'" 
                            @click="actionTab = 'award'"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TabAward', 'Switch to Award issuance form')) }}'"
                            aria-label="{{ lexicon('tooltip_ServiceRecords_TabAward', 'Switch to Award issuance form') }}">
                        {{ lexicon('btn_ServiceRecords_TabAward', 'Award') }}
                    </button>
                    <button type="button" 
                            class="font-bold rounded-input border transition-btn cursor-pointer p-btn-sm text-sm flex-grow" 
                            :class="actionTab === 'promotion' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'" 
                            @click="actionTab = 'promotion'"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TabRank', 'Switch to Rank change form')) }}'"
                            aria-label="{{ lexicon('tooltip_ServiceRecords_TabRank', 'Switch to Rank change form') }}">
                        {{ lexicon('btn_ServiceRecords_TabRank', 'Rank') }}
                    </button>
                    <button type="button" 
                            class="font-bold rounded-input border transition-btn cursor-pointer p-btn-sm text-sm flex-grow" 
                            :class="actionTab === 'transfer' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'" 
                            @click="actionTab = 'transfer'"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TabTransfer', 'Switch to Position transfer form')) }}'"
                            aria-label="{{ lexicon('tooltip_ServiceRecords_TabTransfer', 'Switch to Position transfer form') }}">
                        {{ lexicon('btn_ServiceRecords_TabTransfer', 'Transfer') }}
                    </button>
                    <button type="button" 
                            class="font-bold rounded-input border transition-btn cursor-pointer p-btn-sm text-sm flex-grow" 
                            :class="actionTab === 'status_change' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'" 
                            @click="actionTab = 'status_change'"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TabStatus', 'Switch to Duty Status change form')) }}'"
                            aria-label="{{ lexicon('tooltip_ServiceRecords_TabStatus', 'Switch to Duty Status change form') }}">
                        {{ lexicon('btn_ServiceRecords_TabStatus', 'Status') }}
                    </button>
                    <button type="button" 
                            class="font-bold rounded-input border transition-btn cursor-pointer p-btn-sm text-sm flex-grow" 
                            :class="actionTab === 'manual_entry' ? 'bg-primary text-input border-transparent shadow-btn hover-brightness-110' : 'bg-secondary text-btn-secondary border-secondary hover-bg-border'" 
                            @click="actionTab = 'manual_entry'"
                            x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TabNote', 'Switch to Manual Note entry form')) }}'"
                            aria-label="{{ lexicon('tooltip_ServiceRecords_TabNote', 'Switch to Manual Note entry form') }}">
                        {{ lexicon('btn_ServiceRecords_TabNote', 'Note') }}
                    </button>
                </div>

                {{-- TAB 1: ISSUE AWARD --}}
                <div x-show="actionTab === 'award'" x-cloak>
                    <form action="/admin/service-records/award" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        <div class="flex flex-col gap-xs">
                            <label for="award-recipient-type-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_RecipientType', 'Recipient Type') }}</span>
                            </label>
                            <select id="award-recipient-type-select"
                                    x-model="recipientType" 
                                    aria-label="{{ lexicon('label_ServiceRecords_RecipientType', 'Recipient Type') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_RecipientTypeSelect', 'Select whether award recipient is a user or character')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                <option value="user">{{ lexicon('label_ServiceRecords_OptUserOoc', 'User (OOC)') }}</option>
                                <option value="character">{{ lexicon('label_ServiceRecords_OptCharacterIc', 'Character (IC)') }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs" x-show="recipientType === 'user'" x-cloak>
                            <label for="award-user-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_SelectUser', 'Select User') }}</span>
                                <span class="text-required" x-show="recipientType === 'user'">*</span>
                            </label>
                            <select id="award-user-select"
                                    name="user_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_SelectUser', 'Select User') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_UserSelect', 'Select registered user account')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    :required="recipientType === 'user'">
                                <option value="">{{ lexicon('label_ServiceRecords_ChooseUser', '-- Choose a User --') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs" x-cloak x-show="recipientType === 'character'">
                            <label for="award-character-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}</span>
                                <span class="text-required" x-show="recipientType === 'character'">*</span>
                            </label>
                            <select id="award-character-select"
                                    name="character_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_CharacterSelect', 'Select active character profile')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    :required="recipientType === 'character'">
                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                @foreach($characters as $character)
                                    <option value="{{ $character->id }}">
                                        {{ $character->name }} @if($character->user) ({{ lexicon('label_ServiceRecords_Owner', 'Owner:') }} {{ $character->user->name }}) @endif[cite: 21]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="award-definition-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_AwardToIssue', 'Award to Issue') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="award-definition-select"
                                    name="award_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_AwardToIssue', 'Award to Issue') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_AwardSelect', 'Select award from catalog')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_ServiceRecords_SelectAward', '-- Select an Award --') }}</option>
                                <template x-for="award in filteredAwards" :key="award.id">
                                    <option :value="award.id" x-text="award.category ? `${award.name} (${award.category.name})` : award.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="award-reason-textarea" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_ReasonCitation', 'Reason / Citation') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <textarea id="award-reason-textarea"
                                      name="reason" 
                                      aria-label="{{ lexicon('label_ServiceRecords_ReasonCitation', 'Reason / Citation') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_AwardCitationInput', 'Explain why this award is being issued')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent" 
                                      rows="3" 
                                      placeholder="{{ lexicon('label_ServiceRecords_AwardReasonPlaceholder', 'Explain why this award is being issued...') }}" 
                                      required></textarea>
                        </div>
                        <div class="border-t-actions pt-6 text-right">
                            <x-button type="submit" variant="success" class="w-full">{{ lexicon('btn_ServiceRecords_IssueAward', 'Issue Award') }}</x-button>
                        </div>
                    </form>
                </div>

                {{-- TAB 2: PROMOTION / DEMOTION --}}
                <div x-cloak x-show="actionTab === 'promotion'">
                    <form action="/admin/service-records/promotion" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        <div class="flex flex-col gap-xs">
                            <label for="promo-character-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="promo-character-select"
                                    name="character_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_CharacterSelect', 'Select active character profile')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                @foreach($characters as $character)
                                    <option value="{{ $character->id }}">
                                        {{ $character->name }} @if($character->rank) ({{ lexicon('label_ServiceRecords_Current', 'Current:') }} {{$character->rank->name }}) @endif[cite: 21]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="promo-rank-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_NewAssignedRank', 'New Assigned Rank') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="promo-rank-select"
                                    name="rank_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_NewAssignedRank', 'New Assigned Rank') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_NewRankSelect', 'Select new assigned rank')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_ServiceRecords_OptSelectRank', '-- Select Rank --') }}</option>
                                @foreach($ranks as $rank)
                                    <option value="{{ $rank->id }}">{{ $rank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="promo-reason-textarea" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_ReasonJustification', 'Reason / Justification') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <textarea id="promo-reason-textarea"
                                      name="description" 
                                      aria-label="{{ lexicon('label_ServiceRecords_ReasonJustification', 'Reason / Justification') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_PromotionReasonInput', 'Explain the reason for this promotion or demotion')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent" 
                                      rows="3" 
                                      placeholder="{{ lexicon('label_ServiceRecords_PromotionReasonPlaceholder', 'Explain the reason for this promotion or demotion...') }}" 
                                      required></textarea>
                        </div>
                        <div class="border-t-actions pt-6 text-right">
                            <x-button type="submit" variant="primary" class="w-full">{{ lexicon('btn_ServiceRecords_UpdateRank', 'Update Rank') }}</x-button>
                        </div>
                    </form>
                </div>

                {{-- TAB 3: TRANSFER --}}
                <div x-cloak x-show="actionTab === 'transfer'">
                    <form action="/admin/service-records/transfer" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        <div class="flex flex-col gap-xs">
                            <label for="transfer-character-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="transfer-character-select"
                                    name="character_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_CharacterSelect', 'Select active character profile')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                @foreach($characters as $character)
                                    <option value="{{ $character->id }}">
                                        {{ $character->name }} @if($character->position) ({{ lexicon('label_ServiceRecords_Current', 'Current:') }} {{$character->position->name }}) @endif[cite: 21]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="transfer-position-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_AssignPosition', 'Assign Position') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="transfer-position-select"
                                    name="position_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_AssignPosition', 'Assign Position') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_PositionSelect', 'Select new duty position')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_ServiceRecords_OptSelectPosition', '-- Select Position --') }}</option>
                                @foreach($positions as $position)
                                    <option value="{{ $position->id }}">
                                        {{ $position->name }} @if($position->section) ({{$position->section->name }}) @endif[cite: 21]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="transfer-reason-textarea" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_OrdersReason', 'Orders / Reason') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <textarea id="transfer-reason-textarea"
                                      name="description" 
                                      aria-label="{{ lexicon('label_ServiceRecords_OrdersReason', 'Orders / Reason') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_TransferReasonInput', 'Enter orders or reason for transfer')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent" 
                                      rows="3" 
                                      placeholder="{{ lexicon('label_ServiceRecords_TransferReasonPlaceholder', 'Reason for transfer...') }}" 
                                      required></textarea>
                        </div>
                        <div class="border-t-actions pt-6 text-right">
                            <x-button type="submit" variant="primary" class="w-full">{{ lexicon('btn_ServiceRecords_ExecuteTransfer', 'Execute Transfer') }}</x-button>
                        </div>
                    </form>
                </div>

                {{-- TAB 4: STATUS CHANGE --}}
                <div x-cloak x-show="actionTab === 'status_change'">
                    <form action="/admin/service-records/status-change" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        <div class="flex flex-col gap-xs">
                            <label for="status-character-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="status-character-select"
                                    name="character_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_CharacterSelect', 'Select active character profile')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                @foreach($characters as $character)
                                    <option value="{{ $character->id }}">
                                        {{ $character->name }} ({{ lexicon('label_ServiceRecords_Current', 'Current:') }} {{ strtoupper(str_replace('_', ' ',$character->status)) }})[cite: 21]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="status-value-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_common_Status', 'Status') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="status-value-select"
                                    name="status" 
                                    aria-label="{{ lexicon('label_common_Status', 'Status') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_StatusSelect', 'Select new duty status')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_common_SelectAnOption', '-- Select an Option --') }}</option>
                                <option value="active">{{ lexicon('label_ServiceRecords_StatusActive', 'Active Duty') }}</option>
                                <option value="loa">{{ lexicon('label_ServiceRecords_StatusLoa', 'Leave of Absence (LOA)') }}</option>
                                <option value="eloa">{{ lexicon('label_ServiceRecords_StatusEloa', 'Extended Leave of Absence (ELOA)') }}</option>
                                <option value="honoured_dead">{{ lexicon('label_ServiceRecords_StatusHonouredDead', 'Honoured Dead') }}</option>
                                <option value="memorial">{{ lexicon('label_ServiceRecords_StatusMemorial', 'Memorial') }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="status-reason-textarea" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_StatusNotes', 'Status Change and Justification / Notes') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <textarea id="status-reason-textarea"
                                      name="description" 
                                      aria-label="{{ lexicon('label_ServiceRecords_StatusNotes', 'Status Change and Justification / Notes') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_StatusReasonInput', 'Enter justification for status change')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent" 
                                      rows="3" 
                                      placeholder="{{ lexicon('label_ServiceRecords_StatusReasonPlaceholder', 'Reason for status change...') }}" 
                                      required></textarea>
                        </div>
                        <div class="border-t-actions pt-6 text-right">
                            <x-button type="submit" variant="primary" class="w-full">{{ lexicon('btn_ServiceRecords_UpdateStatus', 'Update Status') }}</x-button>
                        </div>
                    </form>
                </div>

                {{-- TAB 5: MANUAL ENTRY --}}
                <div x-cloak x-show="actionTab === 'manual_entry'">
                    <form action="/admin/service-records/manual-entry" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                        @csrf
                        <div class="flex flex-col gap-xs">
                            <label for="manual-character-select" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <select id="manual-character-select"
                                    name="character_id" 
                                    aria-label="{{ lexicon('label_ServiceRecords_SelectCharacter', 'Select Character') }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_CharacterSelect', 'Select active character profile')) }}'"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent" 
                                    required>
                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                @foreach($characters as $character)
                                    <option value="{{ $character->id }}">{{ $character->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="manual-desc-textarea" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_common_Description', 'Description') }}</span>
                                <span class="text-required">*</span>
                            </label>
                            <textarea id="manual-desc-textarea"
                                      name="description" 
                                      aria-label="{{ lexicon('label_common_Description', 'Description') }}"
                                      x-tooltip="'{{ addslashes(lexicon('tooltip_ServiceRecords_ManualNoteInput', 'Enter detailed service note')) }}'"
                                      x-init="window.autosize && window.autosize($el)"
                                      class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent" 
                                      rows="3" 
                                      placeholder="{{ lexicon('label_ServiceRecords_ManualNotePlaceholder', 'Detailed service note...') }}" 
                                      required></textarea>
                        </div>
                        <div class="border-t-actions pt-6 text-right">
                            <x-button type="submit" variant="primary" class="w-full">{{ lexicon('btn_ServiceRecords_AddEntry', 'Add Entry') }}</x-button>
                        </div>
                    </form>
                </div>

            </x-modal>
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection