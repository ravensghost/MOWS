{{-- resources/views/components/grid-wrapper.blade.php --}}
@props([
    'book' => null,
    'volume' => null,
    'newsItems' => null,
    'storyPosts' => null,
])

@php
    $activeBook = $book ?? $volume;
    $grid = $activeBook?->grid;
    $gridModules = $grid ? $grid->gridModules : collect();
    $bookModules = $activeBook
        ? $activeBook->modules->filter(fn ($m) => (bool) ($m->pivot->is_visible ?? true))
        : collect();

    $normaliseSlotName = static fn (string $name): string => strtolower(preg_replace('/[^a-z0-9]/i', '', $name) ?? '');

    $namedSlots = collect(get_defined_vars())
        ->filter(fn ($value, $name) => $name !== 'slot' && $value instanceof \Illuminate\View\ComponentSlot)
        ->mapWithKeys(fn ($value, $name) => [$normaliseSlotName($name) => $value]);

    $decodeSettings = static function ($raw): array {
        $value = $raw;
        // Unwrap single or double-encoded JSON strings safely
        while (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $value = $decoded;
        }
        return is_array($value) ? $value : [];
    };

    $moduleAliases = [
        'news'                   => 'bulletins',
        'posts'                  => 'folio',
        'folios'                 => 'folio',
        'roster'                 => 'personae_table',
        'user_drafts'            => 'user_folio_drafts',
        'awards'                 => 'distinctions',
        'service_records'        => 'chronological_registers',
        'character_demographics' => 'persona_metadata_summary',
        'character_biography'    => 'persona_biography',
        'character_avatar'       => 'persona_portrait',
    ];

    $renderModule = static function ($module) use ($decodeSettings, $moduleAliases): ?array {
        $clearance = $module->pivot->access_clearance ?? null;
        $canView = empty($clearance) || (auth()->check() && auth()->user()->can($clearance));

        if (! $canView) {
            return null;
        }

        $moduleView = $moduleAliases[$module->module_token] ?? $module->module_token;

        if (! view()->exists('modules.' . $moduleView)) {
            return null;
        }

        $defaultSettings = $decodeSettings($module->module_default_settings ?? []);
        $bookConfig = $decodeSettings($module->pivot->book_config ?? []);

        return [
            'view'     => 'modules.' . $moduleView,
            'settings' => array_merge($defaultSettings, $bookConfig),
        ];
    };
@endphp

<div {{ $attributes->merge(['class' => 'mows-books-grid-wrapper max-w-container mx-auto px-container grid grid-cols-12 gap-4']) }}>
    @forelse($gridModules as $gridModule)
        @php
            $colSpan = $gridModule->column_span ?? 12;
            $rowSpan = $gridModule->row_span ?? 1;
            $areaSlot = $namedSlots->get($normaliseSlotName($gridModule->grid_module_title));
            $assignedModules = $bookModules
                ->where('pivot.grid_module_id', $gridModule->id)
                ->sortBy('pivot.module_sort_order');
        @endphp

        <div class="mows-grid-module col-span-{{ $colSpan }} row-span-{{ $rowSpan }}" data-grid-module-id="{{ $gridModule->id }}">
            @if($areaSlot?->hasActualContent())
                {{ $areaSlot }}
            @endif

            @foreach($assignedModules as $module)
                @php $resolved = $renderModule($module); @endphp
                @if($resolved)
                    @include($resolved['view'], [
                        'book'           => $activeBook,
                        'module'         => $module,
                        'moduleSettings' => $resolved['settings'],
                        'newsItems'      => $newsItems,
                        'storyPosts'     => $storyPosts,
                    ])
                @endif
            @endforeach
        </div>
    @empty
        <div class="col-span-12">
            @foreach($namedSlots as $areaSlot)
                @if($areaSlot->hasActualContent())
                    {{ $areaSlot }}
                @endif
            @endforeach

            @foreach($bookModules->sortBy('pivot.module_sort_order') as $module)
                @php $resolved = $renderModule($module); @endphp
                @if($resolved)
                    @include($resolved['view'], [
                        'book'           => $activeBook,
                        'module'         => $module,
                        'moduleSettings' => $resolved['settings'],
                        'newsItems'      => $newsItems,
                        'storyPosts'     => $storyPosts,
                    ])
                @endif
            @endforeach
        </div>
    @endforelse
</div>