@props([
    'myCharacters' => [],
    'users' => [],
    'characters' => [],
    'threads' => [],
])

<div x-data="{
        isOpen: false,
        activeTab: '{{ count($threads) > 0 ? 'thread-' . $threads->first()->id : 'new' }}',
        pmType: 'OOC',
        users: {{ Js::from($users) }},
        characters: {{ Js::from($characters) }}
     }"
     @open-messenger.window="isOpen = true"
     @close-messenger.window="isOpen = false"
     @ajax-success.window="if (isOpen) { window.updateDOM_US($refs, ['messengerHub']); }"
     class="relative z-modal-50">

    <x-modal show="isOpen" maxWidth="max-w-4xl" onClose="isOpen = false">
        <div class="flex flex-col gap-md max-h-85vh overflow-hidden">
            
            <!-- Modal Header -->
            <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row items-center justify-between">
                <div class="flex flex-row items-center gap-sm">
                    <i class="bi bi-envelope text-accent"></i>
                    <span class="text-lg font-bold text-h">{{ lexicon('public_label_PrivateMessages', 'Private Messages') }}</span>
                </div>
                <button type="button"
                        @click="isOpen = false"
                        class="text-muted bg-transparent hover-text-h cursor-pointer border-none"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'"
                        aria-label="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Modal Body (Two-Column Messaging Hub) -->
            <div x-ref="messengerHub" class="grid lg-grid-sidebar-left gap-md p-panel overflow-y-auto">
                
                <!-- Left Column: Thread List / New Message Toggle -->
                <div class="flex flex-col gap-md">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-xs font-bold text-muted uppercase tracking-wider">{{ lexicon('label_messenger_Conversations', 'Conversations') }}</span>
                        <x-button type="button"
                                  variant="primary"
                                  class="text-xs p-btn-sm"
                                  @click="activeTab = 'new'"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_NewMessage', 'Start a new conversation')) }}'">
                            <i class="bi bi-plus-lg"></i> {{ lexicon('btn_messenger_NewMessage', 'New Message') }}
                        </x-button>
                    </div>

                    <div class="flex flex-col gap-sm overflow-y-auto max-h-60vh pr-1" x-ref="threadsContainer">
                        @forelse($threads ?? [] as $t)
                            <x-message-thread-item :thread="$t" />
                        @empty
                            <div class="p-4 text-center text-muted italic text-sm">
                                <span>{{ lexicon('msg_messenger_NoMessages', 'No messages found.') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Right Column: Active Thread or New Message Form -->
                <div class="flex flex-col gap-md">
                    
                    <!-- NEW MESSAGE VIEW -->
                    <div x-show="activeTab === 'new'" x-cloak class="flex flex-col gap-md">
                        <span class="font-bold text-lg text-h">{{ lexicon('label_messenger_StartNewConversation', 'Start a New Conversation') }}</span>
                        <form action="/messenger/new"
                              method="POST"
                              x-data="ajaxForm"
                              @submit.prevent="submit"
                              data-loading-title="{{ lexicon('msg_messenger_Sending', 'Sending Message...') }}"
                              class="flex flex-col gap-md">
                            @csrf

                            <div class="grid grid-cols-1 md-grid-cols-2 gap-md bg-panel-sub border border-panel rounded-panel p-3">
                                <div class="flex flex-col gap-xs">
                                    <label for="modal_pm_type" class="text-xs font-bold text-input-muted">{{ lexicon('label_common_Context', 'Context') }}</label>
                                    <select id="modal_pm_type"
                                            name="type"
                                            x-model="pmType"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_ContextType', 'Choose Out-Of-Character (User) or In-Character (Character) message')) }}'"
                                            required>
                                        <option value="OOC">{{ lexicon('label_messenger_OocUser', 'Out-Of-Character (User to User)') }}</option>
                                        <option value="IC">{{ lexicon('label_messenger_IcChar', 'In-Character (Character to Character)') }}</option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-xs" x-show="pmType === 'IC'" x-cloak>
                                    <label for="modal_sender_character_id" class="text-xs font-bold text-input-muted">{{ lexicon('label_messenger_SendingAs', 'Sending As') }}</label>
                                    <select id="modal_sender_character_id"
                                            name="sender_character_id"
                                            class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                            x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_SendingAs', 'Select which of your characters is sending this message')) }}'"
                                            :required="pmType === 'IC'">
                                        <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                        @foreach($myCharacters as $myChar)
                                            <option value="{{ $myChar->id }}">{{ $myChar->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label for="modal_recipients" class="text-xs font-bold text-input-muted">{{ lexicon('label_messenger_ToMultiple', 'To: (Search and select multiple)') }}</label>
                                <select id="modal_recipients"
                                        name="recipients[]"
                                        x-init="
                                            if (window.TomSelect) {
                                                const ts = new window.TomSelect($el, { valueField: 'id', labelField: 'name', searchField: 'name', plugins: ['remove_button'] });
                                                ts.addOption(pmType === 'IC' ? characters : users);
                                                $watch('pmType', val => { ts.clear(); ts.clearOptions(); ts.addOption(val === 'IC' ? characters : users); ts.refreshOptions(false); });
                                            }
                                        "
                                        class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                                        multiple
                                        required></select>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label for="modal_subject" class="text-xs font-bold text-input-muted">{{ lexicon('label_messenger_SubjectOptional', 'Subject / Group Name (Optional)') }}</label>
                                <input id="modal_subject"
                                       type="text"
                                       name="subject"
                                       class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                       placeholder="{{ lexicon('label_messenger_SubjectPlaceholder', 'e.g. Planning the weekend event...') }}">
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label for="modal_body" class="text-xs font-bold text-input-muted">{{ lexicon('label_common_Message', 'Message') }}</label>
                                <textarea id="modal_body"
                                          name="body"
                                          rows="3"
                                          class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                                          x-init="window.autosize && window.autosize($el)"
                                          required></textarea>
                            </div>

                            <div class="border-t-actions pt-4 text-right mt-2 flex flex-row items-center justify-end gap-md">
                                <x-button type="submit"
                                          variant="primary"
                                          x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_SendMessage', 'Send this message')) }}'">
                                    {{ lexicon('btn_messenger_SendMessage', 'Send Message') }}
                                </x-button>
                            </div>
                        </form>
                    </div>

                    <!-- THREAD VIEWS -->
                    @foreach($threads ?? [] as $t)
                        <div x-show="activeTab === 'thread-{{ $t->id }}'" x-cloak class="flex flex-col gap-md h-full">
                            <div class="p-3 bg-panel-header border-b border-panel flex flex-row justify-between items-center rounded-t">
                                <span class="font-bold text-base text-h">{{ $t->title ?? lexicon('label_messenger_DirectMessage', 'Direct Message') }}</span>
                                <span class="text-xs text-muted">
                                    {{ lexicon('label_common_Participants', 'Participants:') }}
                                    @foreach($t->participants as $p)
                                        {{ $p->character ? $p->character->name : $p->user->name }}@if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            </div>

                            <!-- Messages History -->
                            <div class="flex flex-col gap-md overflow-y-auto max-h-45vh flex-grow p-2">
                                @foreach($t->messages as $msg)
                                    <div class="flex flex-col {{ $msg->user_id === auth()->id() ? 'items-end' : 'items-start' }}">
                                        <span class="text-xs text-muted mb-1">
                                            {{ $msg->character ? $msg->character->name : $msg->user->name }} - {{ $msg->created_at->format('M d, H:i') }}
                                        </span>
                                        <div class="p-3 rounded-panel max-w-75 {{ $msg->user_id === auth()->id() ? 'bg-panel-sub text-p border border-panel' : 'bg-panel-header text-p border border-panel' }}">
                                            <span class="whitespace-pre-wrap block text-sm">{{ $msg->body }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Reply Form -->
                            <form action="/messenger/{{ $t->id }}/reply"
                                  method="POST"
                                  x-data="ajaxForm"
                                  @submit.prevent="submit"
                                  data-loading-title="{{ lexicon('msg_messenger_SendingReply', 'Sending Reply...') }}"
                                  class="p-3 border-t border-panel flex flex-col gap-md bg-panel-header rounded-b">
                                @csrf
                                @if($t->type === 'IC')
                                    <div class="flex flex-col gap-xs">
                                        <label class="text-xs font-bold text-input-muted">{{ lexicon('label_messenger_SendingAs', 'Sending As') }}</label>
                                        <select name="sender_character_id"
                                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                                x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_SendingAs', 'Select which of your characters is replying')) }}'"
                                                required>
                                            <option value="">{{ lexicon('label_messenger_ReplyingAs', '-- Replying As --') }}</option>
                                            @foreach($myCharacters as $myChar)
                                                <option value="{{ $myChar->id }}">{{ $myChar->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="flex flex-row gap-md items-center">
                                    <textarea name="body"
                                              rows="2"
                                              class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent flex-grow text-sm"
                                              placeholder="{{ lexicon('label_messenger_WriteReplyPlaceholder', 'Write a reply...') }}"
                                              x-init="window.autosize && window.autosize($el)"
                                              required></textarea>
                                    <x-button type="submit"
                                              variant="primary"
                                              class="text-xs"
                                              x-tooltip="'{{ addslashes(lexicon('tooltip_messenger_SendReply', 'Send reply to thread')) }}'">
                                        {{ lexicon('btn_common_Send', 'Send') }}
                                    </x-button>
                                </div>
                            </form>
                        </div>
                    @endforeach

                </div>

            </div>

        </div>
    </x-modal>
</div>