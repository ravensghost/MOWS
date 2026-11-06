@props([
    'label',
    'hint' => null,
    'tooltip' => null,
    'editClick',
    'editTooltip' => null,
])

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