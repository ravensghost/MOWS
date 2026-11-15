{{-- resources/views/components/table.blade.php --}}
@props([
    'type' => null,
    'tbodyAttributes' => null,
    'bareBody' => false,
    'label' => null,
    'hint' => null,
    'tooltip' => null,
    'editClick' => null,
    'editTooltip' => null,
])

@php
    $resolvedType = $type ?? (($label !== null || $editClick !== null) ? 'config' : 'table');
@endphp

@switch($resolvedType)
    @case('config')
    @case('configuration-item')
    @case('item')
    @case('row')
        @php
            $tooltipText = $editTooltip ?? lexicon('tooltip_setting_EditSetting', 'Modify this setting');
        @endphp

        <tr {{ $attributes->merge(['class' => 'border-b-table-row hover-bg-table-row']) }}>
            <td class="p-4 font-bold text-p pl-6">
                <span class="block"
                      @if($tooltip)
                          x-tooltip="'{{ addslashes($tooltip) }}'"
                          aria-label="{{ $tooltip }}"
                      @endif>{{ $label }}</span>
                @if($hint)
                    <span class="block text-xs font-normal text-muted">{{ $hint }}</span>
                @endif
            </td>
            <td class="p-4 text-muted">
                {{ $slot }}
            </td>
            <td class="p-4 text-right">
                <div class="flex flex-row flex-wrap items-center justify-end gap-sm text-sm">
                    <x-button type="button" 
                              variant="text" 
                              class="text-warning hover-text-h flex flex-row items-center gap-xs" 
                              @click="{!! $editClick !!}"
                              x-tooltip="'{{ addslashes($tooltipText) }}'"
                              aria-label="{{ $tooltipText }}">
                        <i class="bi bi-pencil"></i>
                        <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                    </x-button>
                </div>
            </td>
        </tr>
        @break

    @default
        <div {{ $attributes->merge(['class' => 'table-wrapper max-h-table overflow-y-auto w-full']) }}>
            <table class="text-sm text-muted w-full text-left">
                @if(isset($headers))
                    <thead class="bg-table-head text-h uppercase text-xs border-b-table-head">
                        <tr>
                            {{ $headers }}
                        </tr>
                    </thead>
                @endif
                @if($bareBody)
                    {{ $slot }}
                @else
                    <tbody {!! $tbodyAttributes ?? '' !!}>
                        {{ $slot }}
                    </tbody>
                @endif
            </table>
        </div>
@endswitch