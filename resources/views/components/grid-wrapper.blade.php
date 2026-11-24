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
    $bookModules = $activeBook ? $activeBook->modules->where('pivot.is_visible', true) : collect();
    $normaliseSlotName = static fn (string $name): string => strtolower(preg_replace('/[^a-z0-9]/i', '', $name) ?? '');
    $namedSlots = collect(get_defined_vars())
        ->filter(fn ($value, $name) => $name !== 'slot' && $value instanceof \Illuminate\View\ComponentSlot)
        ->mapWithKeys(fn ($value, $name) => [$normaliseSlotName($name) => $value]);
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
                @php
                    $clearance = $module->pivot->access_clearance ?? null;
                    $canView = empty($clearance) || (auth()->check() && auth()->user()->can($clearance));
                    $bookConfig = is_array($module->pivot->book_config)
                        ? $module->pivot->book_config
                        : (json_decode($module->pivot->book_config ?? '{}', true) ?: []);
                    $defaultSettings = is_array($module->module_default_settings)
                        ? $module->module_default_settings
                        : (json_decode($module->module_default_settings ?? '{}', true) ?: []);
                    $moduleSettings = array_merge($defaultSettings, $bookConfig);
                    $moduleAliases = [
                        'news' => 'bulletins',
                        'posts' => 'folio',
                    ];
                    $moduleView = $moduleAliases[$module->module_token] ?? $module->module_token;
                @endphp

                @if($canView && view()->exists('modules.' . $moduleView))
                    @include('modules.' . $moduleView, [
                        'book'           => $activeBook,
                        'module'         => $module,
                        'moduleSettings' => $moduleSettings,
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
                @php
                    $clearance = $module->pivot->access_clearance ?? null;
                    $canView = empty($clearance) || (auth()->check() && auth()->user()->can($clearance));
                    $bookConfig = is_array($module->pivot->book_config)
                        ? $module->pivot->book_config
                        : (json_decode($module->pivot->book_config ?? '{}', true) ?: []);
                    $defaultSettings = is_array($module->module_default_settings)
                        ? $module->module_default_settings
                        : (json_decode($module->module_default_settings ?? '{}', true) ?: []);
                    $moduleSettings = array_merge($defaultSettings, $bookConfig);
                    $moduleAliases = [
                        'news' => 'bulletins',
                        'posts' => 'folio',
                    ];
                    $moduleView = $moduleAliases[$module->module_token] ?? $module->module_token;
                @endphp

                @if($canView && view()->exists('modules.' . $moduleView))
                    @include('modules.' . $moduleView, [
                        'book'           => $activeBook,
                        'module'         => $module,
                        'moduleSettings' => $moduleSettings,
                        'newsItems'      => $newsItems,
                        'storyPosts'     => $storyPosts,
                    ])
                @endif
            @endforeach
        </div>
    @endforelse
</div>