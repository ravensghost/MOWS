{{-- 
@WidgetManifest({
    "name": "Manifest Descriptions",
    "description": "Used internally. Displays section and role descriptions.",
    "hidden": true,
    "fields": {}
})
--}}
@php
    $sections = \App\Models\Section::with('positions')->orderBy('order')->get();
@endphp

<div class="w-full flex flex-col gap-md mb-6" x-data>
    @forelse($sections as $section)
        <x-accordion-panel :title="$section->name" :expanded="true">
            <div class="flex flex-col gap-md">
                {{-- Section Description --}}
                <div class="bg-panel-sub border border-panel rounded-panel p-panel">
                    @if($section->description)
                        <span class="text-sm text-muted block leading-relaxed">{{ $section->description }}</span>
                    @else
                        <span class="text-sm text-muted italic block">{{ lexicon('msg_common_NoSectionDescription', 'No description available for this section.') }}</span>
                    @endif
                </div>

                {{-- Linked Positions List --}}
                @if($section->positions && $section->positions->count() > 0)
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                        @foreach($section->positions as $position)
                            <div class="bg-panel border border-panel rounded-panel p-panel flex flex-col gap-xs">
                                <div class="flex flex-row flex-wrap items-center justify-between gap-sm">
                                    <span class="text-lg font-bold text-accent block">{{ $position->name }}</span>
                                    <x-badge type="info"
                                             aria-label="{{ lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity') }}"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_SectionsPositions_SlotAvailability', 'Current position slot capacity')) }}'">
                                        {{ lexicon('label_SectionsPositions_Capacity', 'Max Capacity') }}: {{ $position->capacity }}
                                    </x-badge>
                                </div>
                                @if($position->description)
                                    <span class="text-sm text-muted block leading-relaxed">{{ $position->description }}</span>
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
                {{ lexicon('msg_common_NoOrganisationalSections', 'No organisational sections have been established yet.') }}
            </span>
        </div>
    @endforelse
</div>