{{-- resources/views/components/modal.blade.php --}}
@props([
    'type' => null,
    'show' => 'open',
    'onClose' => null,
    'maxWidth' => 'max-w-lg',
    'closeOnOutside' => true,
    'backdropClass' => '',
    'formAction' => null,
    'formMethod' => 'POST',
    'activeBooks' => null,
    'allCharacters' => [],
    'languageRatings' => [],
    'sexRatings' => [],
    'violenceRatings' => [],
    'systemPage' => null,
    'myCharacters' => [],
    'users' => null,
    'characters' => [],
    'threads' => null,
])

@php
    if ($type === null) {
        if ($threads !== null || $users !== null) {
            $type = 'messenger';
        } elseif ($activeBooks !== null) {
            $type = 'folio';
        } else {
            $type = 'default';
        }
    }
    $resolvedWidth = match($maxWidth) {
        '4xl', 'max-w-4xl' => 'max-w-4xl',
        'lg', 'max-w-lg'   => 'max-w-lg',
        default            => $maxWidth,
    };
    $closeExpression = $onClose ?? ($show . ' = false');
@endphp

@switch($type)
    @case('messenger')
    @case('message')
    @case('correspondence')
        @php
            $resolvedThreads = $threads ?? collect();
            $resolvedUsers = $users ?? [];
            $resolvedCharacters = $characters ?? [];
            $resolvedMyCharacters = $myCharacters ?? [];
            $firstThreadId = count($resolvedThreads) > 0 ? 'thread-' . collect($resolvedThreads)->first()->id : 'new';
        @endphp
        <div x-data="{
                isOpen: false,
                activeTab: '{{ $firstThreadId }}',
                pmType: 'OOC',
                users: {{ Js::from($resolvedUsers) }},
                characters: {{ Js::from($resolvedCharacters) }},
                initRecipientSelect(el) {
                    if (!window.Select) return;
                    const ts = window.Select.init(el, {
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        plugins: ['remove_button']
                    });
                    if (ts) {
                        ts.clearOptions();
                        ts.addOptions(this.pmType === 'IC' ? this.characters : this.users);
                        ts.refreshOptions(false);
                        this.$watch('pmType', (val) => {
                            ts.clear(true);
                            ts.clearOptions();
                            ts.addOptions(val === 'IC' ? this.characters : this.users);
                            ts.refreshOptions(false);
                        });
                    }
                }
             }"
             @open-messenger.window="isOpen = true"
             @close-messenger.window="isOpen = false"
             @ajax-success.window="if (isOpen && window.updateDOM) { window.updateDOM($refs, ['messengerHub'], null, false); }"
             class="relative">

            <div class="fixed inset-0 z-100 backdrop-bg backdrop-blur flex items-center justify-center p-4 {{ $backdropClass }}" x-cloak x-show="isOpen">
                <div {{ $attributes->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-lg w-full max-w-4xl max-h-90vh flex flex-col overflow-hidden']) }}
                     @click.outside="isOpen = false">

                    <div class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row justify-between items-center gap-md">
                        <div class="flex flex-row items-center gap-sm">
                            <i class="bi bi-envelope text-accent"></i>
                            <span class="text-lg font-bold text-h">{{ lexicon('public_label_PrivateMessages', 'Private Messages') }}</span>
                        </div>
                        <button type="button"
                                @click="isOpen = false"
                                class="text-muted bg-transparent hover-text-h cursor-pointer border-none"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'"
                                title="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}"
                                aria-label="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div x-ref="messengerHub" class="grid lg-grid-sidebar-left gap-md p-panel-body overflow-y-auto flex-grow">
                        <!-- Left Column: Thread List -->
                        <div class="flex flex-col gap-md">
                            <div class="flex flex-row items-center justify-between">
                                <span class="text-xs font-bold text-muted uppercase text-wider">{{ lexicon('label_messenger_Conversations', 'Conversations') }}</span>
                                <x-button type="button"
                                          variant="primary"
                                          size="sm"
                                          icon="bi-plus-lg"
                                          :tooltip="lexicon('tooltip_messenger_NewMessage', 'Start a new conversation')"
                                          @click="activeTab = 'new'">
                                    {{ lexicon('btn_messenger_NewMessage', 'New Message') }}
                                </x-button>
                            </div>

                            <div class="flex flex-col gap-sm overflow-y-auto max-h-64 pr-2" x-ref="threadsContainer">
                                @forelse($resolvedThreads as $t)
                                    @php
                                        $badgeLabel = $t->thread_mode ?? 'OOC';
                                        $badgeVariant = $badgeLabel === 'IC' ? 'info' : 'muted';
                                        $threadTitle = $t->thread_title ?: lexicon('label_messenger_DirectMessage', 'Direct Message');
                                    @endphp
                                    <button type="button"
                                            @click="activeTab = 'thread-{{ $t->id }}'"
                                            :class="activeTab === 'thread-{{ $t->id }}' ? 'border-accent bg-panel-hover' : 'border-panel bg-panel-sub'"
                                            class="w-full text-left p-3 border-rounded transition-colors hover-bg-panel-hover cursor-pointer flex flex-col gap-xs">
                                        <div class="flex flex-row items-center justify-between gap-xs">
                                            <x-badge :variant="$badgeVariant">{{ $badgeLabel }}</x-badge>
                                            <span class="text-xs text-muted">{{ $t->updated_at?->diffForHumans() }}</span>
                                        </div>
                                        <span class="font-bold text-sm text-h">{{ $threadTitle }}</span>
                                    </button>
                                @empty
                                    <div class="p-4 text-center text-muted font-italic text-sm">
                                        <span>{{ lexicon('msg_messenger_NoMessages', 'No messages found.') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Right Column: Active Thread or New Conversation Form -->
                        <div class="flex flex-col gap-md">
                            <div x-show="activeTab === 'new'" x-cloak class="flex flex-col gap-md">
                                <span class="font-bold text-lg text-h">{{ lexicon('label_messenger_StartNewConversation', 'Start a New Conversation') }}</span>
                                <form action="/correspondence/new"
                                      method="POST"
                                      x-data="ajaxForm"
                                      @submit.prevent="submit"
                                      data-loading-title="{{ lexicon('msg_messenger_Sending', 'Sending Message...') }}"
                                      class="flex flex-col gap-md">
                                    @csrf

                                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border-panel border-rounded p-3">
                                        <x-metadata type="select"
                                                    id="modal_pm_type"
                                                    name="thread_mode"
                                                    :label="lexicon('label_common_Context', 'Context')"
                                                    :tooltip="lexicon('tooltip_messenger_ContextType', 'Choose Out-Of-Character (User) or In-Character (Character) message')"
                                                    x-model="pmType"
                                                    required>
                                            <option value="OOC">{{ lexicon('label_messenger_OocUser', 'Out-Of-Character (User to User)') }}</option>
                                            <option value="IC">{{ lexicon('label_messenger_IcChar', 'In-Character (Character to Character)') }}</option>
                                        </x-metadata>

                                        <div x-show="pmType === 'IC'" x-cloak>
                                            <x-metadata type="select"
                                                        id="modal_sender_persona_id"
                                                        name="sender_persona_id"
                                                        :label="lexicon('label_messenger_SendingAs', 'Sending As')"
                                                        :tooltip="lexicon('tooltip_messenger_SendingAs', 'Select which of your characters is sending this message')"
                                                        x-bind:required="pmType === 'IC'">
                                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                                @foreach($resolvedMyCharacters as $myChar)
                                                    <option value="{{ $myChar->id }}">{{ $myChar->persona_name }}</option>
                                                @endforeach
                                            </x-metadata>
                                        </div>
                                    </div>

                                    <x-metadata type="select"
                                                id="modal_recipients"
                                                name="recipients[]"
                                                :label="lexicon('label_messenger_ToMultiple', 'To: (Search and select multiple)')"
                                                multiple
                                                required
                                                x-init="initRecipientSelect($el)" />

                                    <x-metadata type="text"
                                                id="modal_thread_title"
                                                name="thread_title"
                                                :label="lexicon('label_messenger_SubjectOptional', 'Subject / Group Name (Optional)')"
                                                :placeholder="lexicon('label_messenger_SubjectPlaceholder', 'e.g. Planning the weekend event...')" />

                                    <x-metadata type="textarea"
                                                id="modal_correspondence_text"
                                                name="correspondence_text"
                                                :label="lexicon('label_common_Message', 'Message')"
                                                rows="3"
                                                x-init="window.Autosize && window.Autosize.init($el)"
                                                required />

                                    <div class="bg-panel-sub border-t-actions p-4 flex flex-row flex-wrap justify-end items-center gap-sm">
                                        <x-button type="submit"
                                                  variant="primary"
                                                  :tooltip="lexicon('tooltip_messenger_SendMessage', 'Send this message')">
                                            {{ lexicon('btn_messenger_SendMessage', 'Send Message') }}
                                        </x-button>
                                    </div>
                                </form>
                            </div>

                            @foreach($resolvedThreads as $t)
                                @php
                                    $threadEntries = $t->entries ?? collect();
                                    $threadParticipants = $t->participants ?? collect();
                                @endphp
                                <div x-show="activeTab === 'thread-{{ $t->id }}'" x-cloak class="flex flex-col gap-md h-full">
                                    <div class="p-3 bg-panel-header border-b-panel flex flex-row justify-between items-center border-rounded">
                                        <span class="font-bold text-m text-h">{{ $t->thread_title ?: lexicon('label_messenger_DirectMessage', 'Direct Message') }}</span>
                                        <span class="text-xs text-muted">
                                            {{ lexicon('label_common_Participants', 'Participants:') }}
                                            @foreach($threadParticipants as $p)
                                                {{ $p->persona ? $p->persona->persona_name : $p->user?->name }}@if(!$loop->last), @endif
                                            @endforeach
                                        </span>
                                    </div>

                                    <div class="flex flex-col gap-md overflow-y-auto max-h-50vh flex-grow p-2">
                                        @foreach($threadEntries as $msg)
                                            <div class="flex flex-col {{ $msg->user_id === auth()->id() ? 'items-end' : 'items-start' }}">
                                                <span class="text-xs text-muted mb-1">
                                                    {{ $msg->persona ? $msg->persona->persona_name : $msg->user?->name }} - {{ $msg->created_at?->format('M d, H:i') }}
                                                </span>
                                                <div class="p-3 border-rounded max-w-75 {{ $msg->user_id === auth()->id() ? 'bg-panel-sub text-p border-panel' : 'bg-panel-header text-p border-panel' }}">
                                                    <span class="whitespace-pre-wrap block text-sm">{{ $msg->correspondence_text }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <form action="/correspondence/{{ $t->id }}/reply"
                                          method="POST"
                                          x-data="ajaxForm"
                                          @submit.prevent="submit"
                                          data-loading-title="{{ lexicon('msg_messenger_SendingReply', 'Sending Reply...') }}"
                                          class="bg-panel-sub border-t-actions p-4 flex flex-col gap-sm border-rounded">
                                        @csrf
                                        @if($t->thread_mode === 'IC')
                                            <x-metadata type="select"
                                                        name="sender_persona_id"
                                                        :label="lexicon('label_messenger_SendingAs', 'Sending As')"
                                                        :tooltip="lexicon('tooltip_messenger_SendingAs', 'Select which of your characters is replying')"
                                                        required>
                                                <option value="">{{ lexicon('label_messenger_ReplyingAs', '-- Replying As --') }}</option>
                                                @foreach($resolvedMyCharacters as $myChar)
                                                    <option value="{{ $myChar->id }}">{{ $myChar->persona_name }}</option>
                                                @endforeach
                                            </x-metadata>
                                        @endif
                                        <div class="flex flex-row gap-sm items-center">
                                            <textarea name="correspondence_text"
                                                      rows="2"
                                                      class="w-full p-input bg-input text-input border-input border-rounded text-sm transition-colors hover-border-accent flex-grow"
                                                      placeholder="{{ lexicon('label_messenger_WriteReplyPlaceholder', 'Write a reply...') }}"
                                                      x-init="window.Autosize && window.Autosize.init($el)"
                                                      required></textarea>
                                            <x-button type="submit"
                                                      variant="primary"
                                                      size="sm"
                                                      :tooltip="lexicon('tooltip_messenger_SendReply', 'Send reply to thread')">
                                                {{ lexicon('btn_common_Send', 'Send') }}
                                            </x-button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @break

    @case('folio')
        @php
            $resolvedVolumes = $activeBooks ?? [];
            $resolvedActivePersonae = auth()->user()?->personae->where('persona_status', 'active') ?? [];
            $resolvedAllCharacters = $allCharacters ?? [];
            $autoSaveInterval = \App\Models\Configuration::where('configuration_token', 'draft_autosave_interval')->value('configuration_payload') ?: 30000;
        @endphp
        <div x-data="folioEdit"
             @ajax-success.window="closeModal()"
             class="relative">

            <div class="fixed inset-0 z-100 backdrop-bg backdrop-blur flex items-center justify-center p-4 {{ $backdropClass }}" x-cloak x-show="isOpen">
                <div {{ $attributes->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-lg w-full max-w-4xl max-h-90vh flex flex-col overflow-hidden']) }}
                     @click.outside="closeModal()">

                    <div class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row justify-between items-center gap-md">
                        <div class="flex flex-row items-center gap-sm">
                            <i class="bi bi-journal-richtext text-accent"></i>
                            <span class="text-lg font-bold text-h">{{ lexicon('public_label_WriteChapter', 'Write Folio') }}</span>
                        </div>
                        <button type="button"
                                @click="closeModal()"
                                class="text-muted bg-transparent hover-text-h cursor-pointer border-none"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'"
                                title="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}"
                                aria-label="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form action="/folios"
                          method="POST"
                          x-data="ajaxForm"
                          @submit.prevent="submit"
                          class="flex flex-col flex-grow overflow-hidden">
                        @csrf
                        <div x-data="autoSave({{ $autoSaveInterval }})" class="p-panel-body overflow-y-auto flex flex-col gap-md flex-grow">
                            <div class="flex flex-col gap-md">
                                <span class="text-xs font-bold text-muted uppercase text-wider">{{ lexicon('label_WriteChapter_PlacementAndCharacter', 'Placement & Persona') }}</span>

                                <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                                    <x-metadata type="select"
                                                id="modal_volume_id"
                                                name="volume_id"
                                                :label="lexicon('label_WriteChapter_TargetVolume', 'Target Volume')"
                                                :tooltip="lexicon('tooltip_WriteChapter_TargetVolume', 'Select the volume this folio belongs to')"
                                                required>
                                        <option value="">{{ lexicon('label_WriteChapter_SelectVolume', '-- Select a Volume --') }}</option>
                                        @foreach($resolvedVolumes as $volume)
                                            <option value="{{ $volume->id }}">
                                                {{ $volume->volume_title }} ({{ $volume->shelf->shelf_title ?? lexicon('label_Story_LooseVolume', 'Loose') }})
                                            </option>
                                        @endforeach
                                    </x-metadata>

                                    <x-metadata type="select"
                                                id="modal_active_persona_id"
                                                name="active_persona_id"
                                                :label="lexicon('label_WriteChapter_PostingAs', 'Posting As')"
                                                :tooltip="lexicon('tooltip_WriteChapter_PostingAs', 'Select the active persona authoring this folio')"
                                                required>
                                        <option value="">{{ lexicon('label_WriteChapter_SelectPersona', '-- Select Persona --') }}</option>
                                        @foreach($resolvedActivePersonae as $persona)
                                            <option value="{{ $persona->id }}">{{ $persona->persona_name }}</option>
                                        @endforeach
                                    </x-metadata>
                                </div>

                                <div class="grid grid-cols-1 lg-grid-cols-4 gap-md">
                                    <x-metadata type="select"
                                                id="modal_folio_mode"
                                                name="folio_mode"
                                                :label="lexicon('label_WriteChapter_FolioMode', 'Folio Mode')"
                                                :tooltip="lexicon('tooltip_WriteChapter_FolioMode', 'Choose whether this folio is a mission entry or personal log')"
                                                required>
                                        <option value="mission">{{ lexicon('label_WriteChapter_ModeMission', 'Mission') }}</option>
                                        <option value="personal_log">{{ lexicon('label_WriteChapter_ModePersonalLog', 'Personal Log') }}</option>
                                    </x-metadata>

                                    <x-metadata type="number"
                                                id="modal_ic_day"
                                                name="ic_day"
                                                :label="lexicon('label_WriteChapter_InUniverseDay', 'In-Universe Day')"
                                                :tooltip="lexicon('tooltip_WriteChapter_InUniverseDay', 'Enter the in-universe mission day')"
                                                required />

                                    <x-metadata type="text"
                                                id="modal_ic_time"
                                                name="ic_time"
                                                :label="lexicon('label_WriteChapter_InUniverseTime', 'In-Universe Time')"
                                                :tooltip="lexicon('tooltip_WriteChapter_InUniverseTime', 'Enter the in-universe scene time')"
                                                required />

                                    <x-metadata type="select"
                                                id="modal_character_ids"
                                                name="character_ids[]"
                                                :label="lexicon('label_WriteChapter_PersonaePresent', 'Personae Present')"
                                                :tooltip="lexicon('tooltip_WriteChapter_PersonaePresent', 'Select or type NPCs and co-writers participating in this scene')"
                                                multiple
                                                x-init="window.Select && window.Select.init($el, { create: true, plugins: ['remove_button'] })">
                                        @foreach($resolvedAllCharacters as $char)
                                            <option value="{{ $char->id }}">{{ $char->persona_name }}</option>
                                        @endforeach
                                    </x-metadata>
                                </div>

                                <x-metadata type="text"
                                            id="modal_scene_location"
                                            name="scene_location"
                                            :label="lexicon('label_WriteChapter_SceneLocation', 'Scene Location')"
                                            :tooltip="lexicon('tooltip_WriteChapter_SceneLocation', 'Specify the deck, compartment, or location of this scene')"
                                            required />
                            </div>

                            <div class="flex flex-col gap-md pt-2 border-t-panel">
                                <span class="text-xs font-bold text-muted uppercase text-wider">{{ lexicon('label_WriteChapter_ContentAndTiers', 'Folio Content & Content Tiers') }}</span>

                                <x-badge type="ai-rating"
                                         :languageRatings="$languageRatings"
                                         :sexRatings="$sexRatings"
                                         :violenceRatings="$violenceRatings" />

                                <div class="flex flex-col gap-xs">
                                    <label class="text-xs font-bold text-input-muted uppercase text-wider">{{ lexicon('label_WriteChapter_StoryText', 'Story Text') }}</label>
                                    <div class="bg-panel-body border-panel border-rounded shadow-panel overflow-hidden flex flex-col" x-data="tiptapHelper(@js($folio->folio_text ?? ''))">
                                        <x-tt-tb name="folio_text" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-panel-sub border-t-actions p-4 flex flex-row flex-wrap justify-between items-center gap-sm">
                            <x-metadata type="select"
                                        id="modal_folio_status"
                                        name="folio_status"
                                        :label="lexicon('label_WriteChapter_PublishingStatus', 'Publishing Status')"
                                        :tooltip="lexicon('tooltip_WriteChapter_PublishingStatus', 'Save as a private draft or publish immediately to the volume')">
                                <option value="draft">{{ lexicon('label_WriteChapter_StatusDraft', 'Save as Draft') }}</option>
                                <option value="published">{{ lexicon('label_WriteChapter_StatusPublished', 'Publish Immediately') }}</option>
                            </x-metadata>

                            <x-button type="submit"
                                      variant="primary"
                                      :tooltip="lexicon('tooltip_WriteChapter_SaveFolio', 'Save this folio')">
                                {{ lexicon('btn_WriteChapter_SaveFolio', 'Save Folio') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @break

    @case('portrait-forge')
    @case('npc-summary')
    @case('default')
    @default
        <div class="fixed inset-0 z-100 backdrop-bg backdrop-blur flex items-center justify-center p-4 {{ $backdropClass }}" x-cloak x-show="{{ $show }}">
            <div {{ $attributes->merge(['class' => 'bg-panel-body border-panel border-rounded shadow-lg w-full max-h-90vh flex flex-col overflow-hidden ' . $resolvedWidth]) }}
                 @if($closeOnOutside) @click.outside="{{ $closeExpression }}" @endif>

                @if(isset($top))
                    <div class="bg-panel-header text-h border-b-panel p-panel-header flex flex-row justify-between items-center gap-md">
                        <div class="text-lg font-bold text-h flex flex-row items-center gap-sm">
                            {{ $top }}
                        </div>
                        <button type="button"
                                @click="{{ $closeExpression }}"
                                class="text-muted bg-transparent hover-text-h cursor-pointer border-none text-xl font-bold leading-none p-1"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'"
                                title="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}"
                                aria-label="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}">&times;</button>
                    </div>
                @endif

                @if($formAction)
                    <form x-data="ajaxForm"
                          :action="{{ $formAction }}"
                          method="POST"
                          @submit.prevent="submit"
                          class="flex flex-col flex-grow overflow-hidden">
                        @csrf
                        <template x-if="{{ $formMethod }} && {{ $formMethod }} !== 'POST'">
                            <input type="hidden" name="_method" :value="{{ $formMethod }}">
                        </template>

                        <div class="p-panel-body overflow-y-auto flex flex-col gap-md flex-grow">
                            {{ $slot }}
                        </div>

                        @if(isset($bottom))
                            <div class="bg-panel-sub border-t-actions p-4 flex flex-row flex-wrap justify-end items-center gap-sm">
                                {{ $bottom }}
                            </div>
                        @endif
                    </form>
                @else
                    <div class="p-panel-body overflow-y-auto flex flex-col gap-md flex-grow">
                        {{ $slot }}
                    </div>

                    @if(isset($bottom))
                        <div class="bg-panel-sub border-t-actions p-4 flex flex-row flex-wrap justify-end items-center gap-sm">
                            {{ $bottom }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
@endswitch