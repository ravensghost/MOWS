{{-- 
@WidgetManifest({
    "name": "Manifest Descriptions",
    "description": "Used internally. Displays division and assignment descriptions.",
    "hidden": true,
    "fields": {
        "division_catalogue_id": {
            "type": "number",
            "label": "Division Catalogue ID (Optional)",
            "default": ""
        }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options ?? null) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $divisionCatalogueId = !empty($options['division_catalogue_id']) ? (int) $options['division_catalogue_id'] : null;

    $divisions = \App\Models\Division::where('is_visible', true)
        ->when($divisionCatalogueId, function ($query) use ($divisionCatalogueId) {
            $query->where('division_catalogue_id', $divisionCatalogueId);
        })
        ->with(['assignments' => function ($query) {
            $query->where('is_visible', true);
        }])
        ->orderBy('division_sort_order')
        ->get();
@endphp

<div class="w-full flex flex-col gap-md mb-6" x-data>
    @forelse($divisions as $division)
        <x-accordion-panel :title="$division->division_title" :expanded="true">
            <div class="flex flex-col gap-md">
                {{-- Division Description --}}
                <div class="bg-panel-sub border border-panel rounded-panel p-panel">
                    @if($division->division_description)
                        <span class="text-sm text-muted block leading-relaxed">{{ $division->division_description }}</span>
                    @else
                        <span class="text-sm text-muted italic block">{{ lexicon('msg_common_NoSectionDescription', 'No description available for this division.') }}</span>
                    @endif
                </div>

                {{-- Linked Assignments List --}}
                @if($division->assignments && $division->assignments->count() > 0)
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                        @foreach($division->assignments as $assignment)
                            <div class="bg-panel border border-panel rounded-panel p-panel flex flex-col gap-xs">
                                <div class="flex flex-row flex-wrap items-center justify-between gap-sm">
                                    <span class="text-lg font-bold text-accent block">{{ $assignment->assignment_title }}</span>
                                    <x-badge type="info"
                                             aria-label="{{ lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity') }}"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity')) }}'">
                                        {{ lexicon('label_SectionsPositions_Capacity', 'Max Capacity') }}: {{ $assignment->maximum_occupants }}
                                    </x-badge>
                                </div>
                                @if($assignment->assignment_description)
                                    <span class="text-sm text-muted block leading-relaxed">{{ $assignment->assignment_description }}</span>
                                @else
                                    <span class="text-sm text-muted italic block">{{ lexicon('msg_common_NoPositionDescription', 'No description available for this position.') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-accordion-panel>
    @empty
        <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden p-panel text-center">
            <span class="text-muted italic">
                {{ lexicon('msg_common_NoOrganisationalSections', 'No organisational divisions have been established yet.') }}
            </span>
        </div>
    @endforelse
</div>