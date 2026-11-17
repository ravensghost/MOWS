{{-- resources/views/modules/portrait_credits.blade.php --}}
{{-- @WidgetManifest({ "name": "Persona Portrait Credits", "description": "Displays face-claim, attire, backdrop, and art style attribution from portrait_credits.json with zero MySQL queries.", "icon": "bi-person-vcard", "fields": { "title": { "type": "text", "label": "Module Title", "default": "Portrait & Asset Attribution" }, "group_by": { "type": "dropdown", "label": "Group By", "options": {"persona": "Persona / Compartment", "face_claim": "Face-Claim / Artist"}, "default": "persona" }, "show_thumbnails": { "type": "dropdown", "label": "Show Thumbnails?", "options": {"yes": "Yes", "no": "No"}, "default": "yes" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

    $rawTitle = trim((string) ($options['title'] ?? ''));
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_PortraitCredits_DefaultTitle', 'Portrait & Asset Attribution');
    $showThumbnails = ($options['show_thumbnails'] ?? 'yes') === 'yes';

    $creditsLedger = \Illuminate\Support\Facades\Cache::rememberForever('mows_portrait_credits', function () {
        $path = storage_path('app/public/portrait_credits.json');
        if (!file_exists($path)) {
            return [];
        }
        return json_decode((string) file_get_contents($path), true) ?: [];
    });

    $headers = [
        lexicon('label_PortraitCredits_Subject', 'Persona / Subject'),
        lexicon('label_PortraitCredits_Face', 'Face-Claim / Model'),
        lexicon('label_PortraitCredits_Body', 'Uniform / Attire'),
        lexicon('label_PortraitCredits_Background', 'Backdrop / Environment'),
        lexicon('label_common_Status', 'Verification'),
    ];
@endphp

<x-tab type="accordion"
       :title="$title"
       icon="bi-person-vcard"
       :open="true">
    <x-table type="public" :headers="$headers">
        @forelse($creditsLedger as $uriKey => $entry)
            @php
                $subjectName = $entry['persona_name'] ?? $uriKey;
                $status = $entry['status'] ?? 'user_provided';
                $isError = $status === 'uncredited_error';
            @endphp
            <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                <td class="p-4">
                    <div class="flex flex-row items-center gap-sm">
                        @if($showThumbnails)
                            <img src="{{ \Illuminate\Support\Str::startsWith($uriKey, ['http://', 'https://', '/']) ? $uriKey : asset('images/characters/' . $uriKey) }}"
                                 alt="{{ $subjectName }}"
                                 class="w-10 h-10 object-cover border-rounded border-panel block">
                        @endif
                        <span class="font-bold text-h">{{ $subjectName }}</span>
                    </div>
                </td>
                <td class="p-4">{{ $entry['face'] ?? '—' }}</td>
                <td class="p-4">{{ $entry['body'] ?? '—' }}</td>
                <td class="p-4">{{ $entry['background'] ?? '—' }}</td>
                <td class="p-4">
                    @if($isError)
                        <x-badge type="pill"
                                 variant="danger"
                                 icon="bi-exclamation-triangle-fill"
                                 :tooltip="lexicon('error_PortraitCredits_Uncredited', 'Missing attribution credit requires verification')">
                            {{ lexicon('error_PortraitCredits_Uncredited', 'Uncredited') }}
                        </x-badge>
                    @else
                        <x-badge type="pill"
                                 variant="success"
                                 icon="bi-check-circle-fill"
                                 :tooltip="lexicon('tooltip_PortraitCredits_Verified', 'Attribution verified')">
                            {{ strtoupper(str_replace('_', ' ', $status)) }}
                        </x-badge>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="p-6 text-center text-muted font-italic">
                    {{ lexicon('msg_PortraitCredits_Empty', 'No portrait or backdrop attributions recorded yet.') }}
                </td>
            </tr>
        @endforelse
    </x-table>
</x-tab>