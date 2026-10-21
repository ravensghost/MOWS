@props([
    'activeBooks' => [],
    'allCharacters' => [],
    'languageRatings' => [],
    'sexRatings' => [],
    'violenceRatings' => [],
    'systemPage' => null,
])

<div x-data="chapterEdit"
     @ajax-success.window="isOpen = false"
     class="relative z-modal-50">

    <x-modal show="isOpen" maxWidth="max-w-4xl" onClose="isOpen = false">
        <div class="flex flex-col gap-md max-h-85vh overflow-hidden">
            
            <!-- Modal Header -->
            <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row items-center justify-between">
                <div class="flex flex-row items-center gap-sm">
                    <i class="bi bi-journal-richtext text-accent"></i>
                    <span class="text-lg font-bold text-h">{{ lexicon('public_label_WriteChapter', 'Write Chapter') }}</span>
                </div>
                <button type="button"
                        @click="isOpen = false"
                        class="text-muted bg-transparent hover-text-h cursor-pointer border-none"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'"
                        aria-label="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            @php
                $autoSaveInterval = \App\Models\Setting::where('key', 'draft_autosave_interval')->value('value') ?: 30000;
            @endphp
            <div x-data="autoSave({{ $autoSaveInterval }})" class="flex flex-col gap-md p-panel overflow-y-auto max-h-70vh">
            <form action="/chapters"
                  method="POST"
                  x-data="ajaxForm"
                  @submit.prevent="submit"
                  data-loading-title="{{ lexicon('msg_common_Saving', 'Saving...') }}"
                  >
                @csrf

                <!-- Placement & Character -->
                <div class="flex flex-col gap-md">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">{{ lexicon('label_WriteChapter_PlacementAndCharacter', 'Placement & Character') }}</span>
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label for="modal_book_id" class="text-xs font-bold text-input-muted">{{ lexicon('label_WriteChapter_TargetBook', 'Target Book') }}</label>
                            <select id="modal_book_id"
                                    name="book_id"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_TargetBook', 'Select the book for this chapter')) }}'"
                                    required>
                                <option value="">{{ lexicon('label_common_SelectABook', '-- Select a Book --') }}</option>
                                @foreach($activeBooks ?? [] as $book)
                                    <option value="{{ $book->id }}">
                                        {{ $book->title }} ({{ $book->bookcase->name ?? lexicon('label_WriteChapter_LooseBook', 'Loose') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label for="modal_active_character_id" class="text-xs font-bold text-input-muted">{{ lexicon('label_WriteChapter_PostingAs', 'Posting As') }}</label>
                            <select id="modal_active_character_id"
                                    name="active_character_id"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_PostingAs', 'Select your active character posting this chapter')) }}'"
                                    required>
                                <option value="">{{ lexicon('label_common_SelectCharacter', '-- Select Character --') }}</option>
                                @foreach(auth()->user()?->characters->where('status', 'active') ?? [] as $character)
                                    <option value="{{ $character->id }}">{{ $character->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg-grid-cols-4 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label for="modal_chapter_type" class="text-xs font-bold text-input-muted">{{ lexicon('label_WriteChapter_ChapterType', 'Chapter Type') }}</label>
                            <select id="modal_chapter_type"
                                    name="chapter_type"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_ChapterType', 'Select whether this is a mission post or personal log')) }}'"
                                    required>
                                <option value="mission">{{ lexicon('label_WriteChapter_Mission', 'Mission') }}</option>
                                <option value="personal_log">{{ lexicon('label_WriteChapter_PersonalLog', 'Personal Log') }}</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <x-form-input id="modal_ic_day"
                                          name="ic_day"
                                          type="text"
                                          :label="lexicon('label_WriteChapter_InUniverseDay', 'In-Universe Day')"
                                          :placeholder="lexicon('label_WriteChapter_PlaceholderDay', 'e.g. 14')"
                                          :tooltip="lexicon('tooltip_WriteChapter_InUniverseDay', 'Enter the in-universe day number')"
                                          :required="true" />
                        </div>

                        <div class="flex flex-col gap-xs">
                            <x-form-input id="modal_ic_time"
                                          name="ic_time"
                                          type="text"
                                          :label="lexicon('label_WriteChapter_InUniverseTime', 'In-Universe Time')"
                                          :placeholder="lexicon('label_WriteChapter_PlaceholderTime', 'e.g. 14:00')"
                                          :tooltip="lexicon('tooltip_WriteChapter_InUniverseTime', 'Enter the in-universe time')"
                                          :required="true" />
                        </div>

                        <div class="flex flex-col gap-xs"
                            x-init="window.TomSelect && new window.TomSelect($refs.modalMultiCharSelect, { 
                                plugins: ['remove_button'], 
                                create: true, 
                                createOnBlur: true,
                                placeholder: '{{ addslashes(lexicon('label_WriteChapter_PlaceholderAddCharacters', 'Add other characters...')) }}' 
                            })">
                            <label for="modal_character_ids" class="text-xs font-bold text-input-muted">{{ lexicon('label_WriteChapter_CharactersPresent', 'Characters Present') }}</label>
                            <select id="modal_character_ids"
                                    x-ref="modalMultiCharSelect"
                                    name="character_ids[]"
                                    multiple
                                    class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent"
                                    autocomplete="off"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_CharactersPresent', 'Select other characters present in this scene')) }}'">
                                @foreach($allCharacters ?? [] as $char)
                                    <option value="{{ $char->id }}">{{ $char->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <x-form-input id="modal_location"
                                      name="location"
                                      type="text"
                                      :label="lexicon('label_common_Location', 'Location')"
                                      :placeholder="lexicon('label_WriteChapter_PlaceholderLocation', 'e.g. Sector 4, Main Hall')"
                                      :tooltip="lexicon('tooltip_WriteChapter_Location', 'Enter the in-universe location of this scene')"
                                      :required="true" />
                    </div>
                </div>

                <!-- Chapter Content & Ratings -->
                <div class="flex flex-col gap-md pt-2 border-t border-panel">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">{{ lexicon('label_WriteChapter_ChapterContent', 'Chapter Content') }}</span>
                    
                    <x-chapter-ratings :languageRatings="$languageRatings" :sexRatings="$sexRatings" :violenceRatings="$violenceRatings" />

                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">{{ lexicon('label_WriteChapter_TheStory', 'The Story') }}</label>
                        <span class="text-xs text-muted block">{{ lexicon('msg_WriteChapter_SanitizedHint', 'Use the rich text editor below. Content is automatically sanitised.') }}</span>

                        <div class="bg-panel border-panel rounded-panel shadow-panel overflow-hidden flex flex-col" x-data="tiptapEditor(@js($chapter->content ?? ''))">
                            <x-tiptap-toolbar />
                            <div x-ref="editorContainer" class="tiptap-content w-full p-4 min-h-32 flex-grow"></div>
                            <input type="hidden" name="content" x-model="content" required>
                        </div>
                    </div>
                </div>

                <!-- Publishing Status & Submit -->
                <div class="border-t-actions pt-4 mt-2 flex flex-row flex-wrap justify-between items-center gap-md"
                     x-data="{ autoSaved: false, savingNow: false }"
                     @autosave-success.window="savingNow = false; autoSaved = true;"
                     @autosave-start.window="savingNow = true;">
                    
                    <div class="flex flex-col gap-xs w-56">
                        <label for="modal_chapter_status" class="text-xs font-bold text-input-muted">{{ lexicon('label_WriteChapter_PublishingStatus', 'Publishing Status') }}</label>
                        <select id="modal_chapter_status"
                                name="status"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_PublishingStatus', 'Choose whether to save as a private draft or publish immediately')) }}'">
                            <option value="draft">{{ lexicon('label_WriteChapter_SaveAsDraft', 'Save as Draft') }}</option>
                            <option value="published">{{ lexicon('label_WriteChapter_PublishImmediately', 'Publish Immediately') }}</option>
                        </select>
                    </div>

                    <!-- Auto-Save Status Badge & Save Button -->
                    <div class="flex flex-row items-center gap-sm">
                        <!-- Live Auto-Save Badge -->
                        <div class="flex items-center gap-xs px-2 py-1 rounded bg-panel-sub text-xs text-muted border border-panel"
                             x-tooltip="'Background draft synchronization status'">
                            <template x-if="savingNow">
                                <span class="flex items-center gap-xs text-warning">
                                    <i class="bi bi-arrow-repeat animate-spin"></i> Saving...
                                </span>
                            </template>
                            <template x-if="!savingNow">
                                <span>
                                    <template x-if="!autoSaved">
                                        <span class="text-danger flex items-center gap-xs" title="Not auto-saved yet">
                                            <i class="bi bi-x-lg"></i> Unsaved
                                        </span>
                                    </template>
                                    <template x-if="autoSaved">
                                        <span class="text-success flex items-center gap-xs" title="Draft auto-saved successfully">
                                            <i class="bi bi-check-lg"></i> Auto-Saved
                                        </span>
                                    </template>
                                </span>
                            </template>
                        </div>

                        <x-button type="submit"
                                  variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_SaveChapter', 'Save or publish this chapter')) }}'">
                            {{ lexicon('btn_WriteChapter_SaveChapter', 'Save Chapter') }}
                        </x-button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </x-modal>
</div>