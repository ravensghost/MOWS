@props([
    'activeBooks' => [],
    'allCharacters' => [],
    'languageRatings' => [],
    'sexRatings' => [],
    'violenceRatings' => [],
    'systemPage' => null,
])

<div x-data="folioEdit"
     @ajax-success.window="isOpen = false"
     class="relative z-modal-50">

    <x-modal show="isOpen" maxWidth="max-w-4xl" onClose="isOpen = false">
        <div class="flex flex-col gap-md max-h-85vh overflow-hidden">
            
            <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row items-center justify-between">
                <div class="flex flex-row items-center gap-sm">
                    <i class="bi bi-journal-richtext text-accent"></i>
                    <span class="text-lg font-bold text-h">{{ lexicon('public_label_WriteChapter', 'Write Folio') }}</span>
                </div>
                <button type="button" @click="isOpen = false" class="text-muted bg-transparent hover-text-h cursor-pointer border-none">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            @php
                $autoSaveInterval = \App\Models\Configuration::where('configuration_token', 'draft_autosave_interval')->value('configuration_payload') ?: 30000;
            @endphp
            <div x-data="autoSave({{ $autoSaveInterval }})" class="flex flex-col gap-md p-panel overflow-y-auto max-h-70vh">
                <form action="/folios" method="POST" x-data="ajaxForm" @submit.prevent="submit">
                    @csrf

                    <div class="flex flex-col gap-md">
                        <span class="text-xs font-bold text-muted uppercase tracking-wider">{{ lexicon('label_WriteChapter_PlacementAndCharacter', 'Placement & Persona') }}</span>
                        
                        <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label for="modal_volume_id" class="text-xs font-bold text-input-muted">Target Volume</label>
                                <select id="modal_volume_id" name="volume_id" class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border" required>
                                    <option value="">-- Select a Volume --</option>
                                    @foreach($activeBooks ?? [] as $volume)
                                        <option value="{{ $volume->id }}">
                                            {{ $volume->volume_title }} ({{ $volume->shelf->shelf_title ?? 'Loose' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label for="modal_active_persona_id" class="text-xs font-bold text-input-muted">Posting As</label>
                                <select id="modal_active_persona_id" name="active_persona_id" class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border" required>
                                    <option value="">-- Select Persona --</option>
                                    @foreach(auth()->user()?->personae->where('persona_status', 'active') ?? [] as $persona)
                                        <option value="{{ $persona->id }}">{{ $persona->persona_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg-grid-cols-4 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label for="modal_folio_mode" class="text-xs font-bold text-input-muted">Folio Mode</label>
                                <select id="modal_folio_mode" name="folio_mode" class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border" required>
                                    <option value="mission">Mission</option>
                                    <option value="personal_log">Personal Log</option>
                                </select>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <x-form-input id="modal_ic_day" name="ic_day" type="number" :label="lexicon('label_WriteChapter_InUniverseDay', 'In-Universe Day')" required />
                            </div>

                            <div class="flex flex-col gap-xs">
                                <x-form-input id="modal_ic_time" name="ic_time" type="text" :label="lexicon('label_WriteChapter_InUniverseTime', 'In-Universe Time')" required />
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label for="modal_character_ids" class="text-xs font-bold text-input-muted">Personae Present</label>
                                <select id="modal_character_ids" name="character_ids[]" multiple class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border">
                                    @foreach($allCharacters ?? [] as $char)
                                        <option value="{{ $char->id }}">{{ $char->persona_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <x-form-input id="modal_scene_location" name="scene_location" type="text" label="Scene Location" required />
                        </div>
                    </div>

                    <div class="flex flex-col gap-md pt-sm border-t-panel">
                        <span class="text-xs font-bold text-muted uppercase tracking-wider">Folio Content & Content Tiers</span>
                        
                        <x-content-tier-badge :languageRatings="$languageRatings" :sexRatings="$sexRatings" :violenceRatings="$violenceRatings" />

                        <div class="flex flex-col gap-xs">
                            <label class="text-xs font-bold text-input-muted">Story Text</label>
                            <div class="bg-panel border-panel rounded-panel shadow-panel overflow-hidden flex flex-col" x-data="tiptapEditor(@js($folio->folio_text ?? ''))">
                                <x-tiptap-toolbar />
                                <div x-ref="editorContainer" class="tiptap-content w-full p-panel flex-grow"></div>
                                <input type="hidden" name="folio_text" x-model="content" required>
                            </div>
                        </div>
                    </div>

                    <div class="border-t-actions pt-md mt-sm flex flex-row flex-wrap justify-between items-center gap-md">
                        <div class="flex flex-col gap-xs">
                            <label for="modal_folio_status" class="text-xs font-bold text-input-muted">Publishing Status</label>
                            <select id="modal_folio_status" name="folio_status" class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border">
                                <option value="draft">Save as Draft</option>
                                <option value="published">Publish Immediately</option>
                            </select>
                        </div>

                        <x-button type="submit" variant="primary">Save Folio</x-button>
                    </div>
                </form>
            </div>
        </div>
    </x-modal>
</div>