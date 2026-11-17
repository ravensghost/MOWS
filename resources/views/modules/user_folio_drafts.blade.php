{{-- resources/views/modules/user_folio_drafts.blade.php --}}
{{-- @WidgetManifest({ "name": "Draft Folios", "description": "Displays the user's unpublished folio drafts.", "icon": "bi-journal-text", "fields": { "title": { "type": "text", "label": "Module Title", "default": "My Folio Drafts" } } }) --}}
@php
    $rawOptions =$moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ?$rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));$title = $rawTitle !== '' ?$rawTitle : lexicon('label_DraftFolios_DefaultTitle', 'My Folio Drafts');

    $draftFolios =$userDrafts ?? collect();

    if ($draftFolios->isEmpty() && auth()->check()) {
        $userId = auth()->id();$draftFolios = \App\Models\Folio::with(['volume', 'activePersona'])
            ->where('folio_status', 'draft')
            ->where(function ($q) use ($userId) {
                $q->where('user_id',$userId)
                  ->orWhere('turn_user_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->get();
    }
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-journal-text"
       :open="true">
    <x-slot:top>
        <x-badge type="pill"
                 :variant="$draftFolios->isNotEmpty() ? 'info' : 'muted'"
                 :tooltip="lexicon('tooltip_DraftFolios_Count', 'Active unpublished folio drafts')">
            {{ $draftFolios->count() }}
        </x-badge>
    </x-slot:top>

    @if($draftFolios->isEmpty())
        <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center flex flex-col items-center justify-center gap-xs">
            <i class="bi bi-journal-check text-2xl text-muted" aria-hidden="true"></i>
            <span class="text-sm text-muted font-italic">{{ lexicon('msg_DraftFolios_None', 'You have no unpublished folio drafts.') }}</span>
        </div>
    @else
        <div class="flex flex-col gap-sm" x-data>
            @foreach($draftFolios as$draft)
                @php
                    $volumeTitle =$draft->volume->volume_title ?? lexicon('label_common_UnknownVolume', 'Unassigned Volume');
                    $personaName =$draft->activePersona->persona_name ?? lexicon('label_common_Narrator', 'Narrator');
                    $location =$draft->scene_location ?: lexicon('label_common_UnknownLocation', 'Unspecified Location');
                    $isMyTurn = auth()->check() && (int) ($draft->turn_user_id ?? 0) === (int) auth()->id();
                @endphp

                <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-row flex-wrap items-center justify-between gap-sm hover-border-accent transition-colors">
                    <div class="flex flex-col gap-xs">
                        <div class="flex flex-row flex-wrap items-center gap-xs">
                            <span class="text-sm font-bold text-h">{{ $location }}</span>
                            <x-badge type="pill"
                                     variant="muted"
                                     :tooltip="lexicon('tooltip_Folio_Volume', 'Target Volume')">
                                {{ $volumeTitle }}
                            </x-badge>
                            @if($isMyTurn)
                                <x-badge type="pill"
                                         variant="warning"
                                         icon="bi-pen"
                                         :tooltip="lexicon('tooltip_DraftFolios_YourTurn', 'Awaiting your co-writing turn')">
                                    {{ lexicon('label_DraftFolios_YourTurn', 'Your Turn') }}
                                </x-badge>
                            @endif
                        </div>

                        <span class="text-xs text-muted">
                            {{ lexicon('label_Folio_AuthorPersona', 'Posting As') }}: <strong class="text-p">{{ $personaName }}</strong>
                            &bull; {{ lexicon('label_common_Day', 'Day') }} {{ $draft->ic_day ?? 1 }}, {{ $draft->ic_time ?? '0800' }}
                        </span>
                    </div>

                    <x-button type="button"
                              variant="primary"
                              size="sm"
                              icon="bi-pencil-square"
                              :tooltip="lexicon('tooltip_DraftFolios_Resume', 'Resume editing this folio draft')"
                              @click="$dispatch('open-folio-modal', { folioId: {{$draft->id }} })">
                        {{ lexicon('btn_DraftFolios_Resume', 'Resume Draft') }}
                    </x-button>
                </div>
            @endforeach
        </div>
    @endif
</x-tab>