@props([
    'show' => 'open',
    'onClose' => null,
    'maxWidth' => 'max-w-lg',
    'closeOnOutside' => true,
    'backdropClass' => '',
])

<!-- The Backdrop -->
<div class="bg-backdrop backdrop-blur fixed inset-0 flex flex-col items-center justify-start p-4 pt-6 z-100 {{ $backdropClass }}" x-cloak x-show="{{ $show }}">
    
    <!-- The Panel -->
    <div {{ $attributes->merge(['class' => 'relative w-full bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden ' . $maxWidth]) }}
         @if($closeOnOutside) @click.outside="{{ $onClose ?? $show . ' = false' }}" @endif>
        
        @if(isset($top))
            <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap gap-md justify-between items-center">
                <span class="text-xl font-bold text-h">{{ $top }}</span>
                <button type="button"
                        @click="{{ $onClose ?? $show . ' = false' }}"
                        class="text-muted bg-transparent hover-text-h hover-bg-close absolute top-4 right-4 text-xl font-bold leading-none border-none p-1 rounded cursor-pointer"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_CloseModal', 'Close Modal')) }}'"
                        aria-label="{{ lexicon('tooltip_common_CloseModal', 'Close Modal') }}">&times;</button>
            </div>
        @endif

        <div class="p-panel flex flex-col gap-md flex-grow overflow-y-auto max-h-90vh">
            {{ $slot }}
        </div>

        @if(isset($bottom))
            <div class="border-t-actions p-panel text-right flex flex-row flex-wrap items-center justify-end gap-md">
                {{ $bottom }}
            </div>
        @endif
    </div>
</div>