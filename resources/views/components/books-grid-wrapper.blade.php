@props([
    'book',
    'newsItems' => null,
    'storyPosts' => null,
])

@php
    $grid = $book?->grid;
    $gridModules = $grid ? $grid->gridModules : collect();
    $bookModules = $book ? $book->modules->where('pivot.is_visible', true) : collect();
@endphp

<div class="mows-books-grid-wrapper grid grid-cols-12 gap-4">
    @forelse($gridModules as $gridModule)
        @php
            $colSpan = $gridModule->column_span ?? 12;
            $rowSpan = $gridModule->row_span ?? 1;
            $assignedModules = $bookModules
                ->where('pivot.grid_module_id', $gridModule->id)
                ->sortBy('pivot.module_sort_order');
        @endphp

        <div class="mows-grid-module col-span-{{ $colSpan }} row-span-{{ $rowSpan }}" data-grid-module-id="{{ $gridModule->id }}">
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
                @endphp

                @if($canView && view()->exists('widgets.' . $module->module_token))
                    @include('widgets.' . $module->module_token, [
                        'book'           => $book,
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
                @endphp

                @if($canView && view()->exists('widgets.' . $module->module_token))
                    @include('widgets.' . $module->module_token, [
                        'book'           => $book,
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