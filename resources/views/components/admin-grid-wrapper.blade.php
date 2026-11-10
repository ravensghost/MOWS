@props([
    'page',
    'newsItems' => null,
    'storyPosts' => null,
])

@php
    $normalisedSlots = [];
    foreach (get_defined_vars() as $key => $value) {
        $cleanKey = strtolower(str_replace([' ', '_', '-'], '', $key));
        $normalisedSlots[$cleanKey] = $value;
    }
@endphp

<div>
    @if($page && $page->grid)
        <div class="grid grid-cols-12 auto-rows-min gap-md w-full" x-ref="pageLayoutGrid">
            @foreach($page->grid->gridModules as $block)
                <div class="col-span-{{ $block->column_span }} row-span-{{ $block->row_span }} flex flex-col gap-md pr-2">
                    
                    @php
                        $targetSlot = strtolower(str_replace([' ', '_', '-'], '', $block->grid_modules_title));
                    @endphp

                    @if(isset($normalisedSlots[$targetSlot]))
                        {{ $normalisedSlots[$targetSlot] }}
                    @else
                        @php
                            // Filter widgets by grid_module_id and ensure visibility
                            $blockWidgets = $page->module
                                ? $page->module
                                    ->where('pivot.grid_module_id', $block->id)
                                    ->filter(fn($w) => (bool) ($w->pivot->is_visible ?? true))
                                : collect();
                        @endphp
                        
                        @foreach($blockWidgets as $widget)
                            @php
                                $viewKey = str_replace('widget_', '', $widget->modules_token);
                            @endphp
                            @includeIf('widgets.' . $viewKey, ['widget' => $widget, 'newsItems' => $newsItems, 'storyPosts' => $storyPosts])
                        @endforeach
                    @endif
                    
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-4" x-ref="emptyStatePanel">
            <div class="text-muted italic p-6 text-center">
                <span class="text-muted italic block">
                    {{ lexicon('msg_PageBuilder_NoLayout', 'This page does not have a grid layout assigned yet. Please configure it in the Command Centre.') }}
                </span>
            </div>
        </div>
        
        @if(isset($normalisedSlots['maincontent']))
            {{ $normalisedSlots['maincontent'] }}
        @elseif(isset($slot))
            {{ $slot }}
        @endif
    @endif
</div>