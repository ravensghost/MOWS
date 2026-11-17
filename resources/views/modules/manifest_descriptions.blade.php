{{-- resources/views/modules/manifest_descriptions.blade.php --}}
{{-- @WidgetManifest({ "name": "Manifest Descriptions", "description": "Used internally. Displays division and assignment descriptions.", "hidden": true, "fields": { "division_catalogue_id": { "type": "number", "label": "Division Catalogue ID (Optional)", "default": "" } } }) --}}
@php
    $rawOptions = $moduleSettings
        ?? $module?->pivot?->book_config
        ?? $module?->pivot?->instance_options
        ?? $widget?->pivot?->instance_options
        ?? [];
    $options = is_string($rawOptions) ? (json_decode($rawOptions, true) ?: []) : (is_array($rawOptions) ? $rawOptions : []);

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

<div class="flex flex-col gap-md w-full">
    @forelse($divisions as $division)
        <x-tab type="accordion"
               :title="$division->division_title"
               icon="bi-diagram-3"
               :open="true"
               :style="'--section-color: ' . ($division->division_base_colour ?? '#374151FF') . ';'"
               headerClass="border-l-divisions">
            @if(!empty($division->division_description))
                <p class="text-sm text-p leading-relaxed m-0 pb-2 border-b-panel">
                    {{ $division->division_description }}
                </p>
            @endif

            @if($division->assignments && $division->assignments->isNotEmpty())
                <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                    @foreach($division->assignments as $assignment)
                        <x-tab type="sub"
                               :title="$assignment->assignment_title"
                               icon="bi-person-workspace">
                            <x-slot:top>
                                <x-badge type="pill"
                                         variant="info"
                                         :tooltip="lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity')">
                                    {{ lexicon('label_SectionsPositions_Capacity', 'Max Capacity') }}: {{ $assignment->maximum_occupants }}
                                </x-badge>
                            </x-slot:top>

                            @if(!empty($assignment->assignment_description))
                                <span class="text-sm text-p block leading-relaxed">{{ $assignment->assignment_description }}</span>
                            @else
                                <span class="text-sm text-muted font-italic block">{{ lexicon('msg_common_NoPositionDescription', 'No description available for this position.') }}</span>
                            @endif
                        </x-tab>
                    @endforeach
                </div>
            @else
                <div class="bg-panel-sub border-dashed-panel border-rounded p-3 text-center">
                    <span class="text-xs text-muted font-italic">
                        {{ lexicon('msg_common_NoAssignmentsInDivision', 'No duty assignments have been configured for this division.') }}
                    </span>
                </div>
            @endif
        </x-tab>
    @empty
        <x-tab type="panel">
            <div class="bg-panel-sub border-dashed-panel border-rounded p-panel-body text-center">
                <span class="text-sm text-muted font-italic">
                    {{ lexicon('msg_common_NoOrganisationalSections', 'No organisational divisions have been established yet.') }}
                </span>
            </div>
        </x-tab>
    @endforelse
</div>