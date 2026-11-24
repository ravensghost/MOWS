{{-- resources/views/components/table.blade.php --}}
@props([
    'type' => null,
    'headers' => null,
    'rows' => [],
    'tbodyAttributes' => null,
    'bareBody' => false,
    'label' => null,
    'hint' => null,
    'tooltip' => null,
    'editClick' => null,
    'editTooltip' => null,
    'emptyText' => null,
])

@php
    $type =$type ?? (($label !== null \vert{}\vert{}$editClick !== null) ? 'config' : 'admin');
@endphp

@switch($type)
    @case('config')
    @case('configuration-item')
    @case('item')
    @case('row')
        @php
            $tooltipText =$editTooltip ?? lexicon('tooltip_setting_EditSetting', 'Modify this setting');
        @endphp
        <tr {{ $attributes->merge(['class' => 'border-b-table-row hover-bg-panel-hover transition-colors']) }}>
            <td class="p-4 font-bold text-p">
                <span class="block"
                      @if($tooltip)
                          x-tooltip="'{{ addslashes($tooltip) }}'"
                          title="{{ $tooltip }}"
                          aria-label="{{ $tooltip }}"
                      @endif>{{ $label }}</span>
                @if($hint)
                    <span class="block text-xs text-muted">{{ $hint }}</span>
                @endif
            </td>
            <td class="p-4 text-sm text-p">
                {{ $slot }}
            </td>
            <td class="p-4 text-right">
                <div class="flex flex-row flex-wrap items-center justify-end gap-sm">
                    @if($editClick)
                        <x-button type="button"
                                  variant="ghost"
                                  size="sm"
                                  icon="bi-pencil"
                                  :tooltip="$tooltipText"
                                  @click="{!! $editClick !!}">
                            {{ lexicon('btn_common_Edit', 'Edit') }}
                        </x-button>
                    @endif
                </div>
            </td>
        </tr>
        @break

    @case('public')
        <div {{ $attributes->merge(['class' => 'w-full overflow-y-auto max-h-table border-panel border-rounded bg-panel-body shadow-panel']) }}>
            <table class="w-full text-left">
                @if(is_array($headers) && !empty($headers))
                    <thead class="bg-table-head text-h border-b-table-head text-xs uppercase text-wider font-bold">
                        <tr>
                            @foreach($headers as $headerLabel)
                                <th class="p-4">{{ $headerLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @elseif(isset($headers))
                    <thead class="bg-table-head text-h border-b-table-head text-xs uppercase text-wider font-bold">
                        <tr>
                            {{ $headers }}
                        </tr>
                    </thead>
                @endif

                @if($bareBody)
                    {{ $slot }}
                @else
                    <tbody class="text-sm text-p" {!! $tbodyAttributes ?? '' !!}>
                        @if(!empty($rows))
                            @foreach($rows as $row)
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                    <td class="p-4 font-bold text-p">
                                        <span>{{ $row['label'] ?? '' }}</span>
                                        @if(!empty($row['hint']))
                                            <span class="block text-xs text-muted">{{ $row['hint'] }}</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm text-p">
                                        {{ $row['value'] ?? '' }}
                                    </td>
                                </tr>
                            @endforeach
                        @elseif($slot->isNotEmpty())
                            {{ $slot }}
                        @else
                            <tr>
                                <td colspan="100" class="p-6 text-center text-muted font-italic">
                                    {{ $emptyText ?? lexicon('msg_common_NoRecordsFound', 'No records found.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @endif
            </table>
        </div>
        @break

    @case('admin')
    @case('table')
    @default
        <div {{ $attributes->merge(['class' => 'w-full overflow-y-auto max-h-table border-panel border-rounded bg-panel-body shadow-panel']) }}>
            <table class="w-full text-left">
                @if(is_array($headers) && !empty($headers))
                    <thead class="bg-table-head text-h border-b-table-head text-xs uppercase text-wider font-bold">
                        <tr>
                            @foreach($headers as $index =>$headerLabel)
                                <th class="p-4 {{ $index === count($headers) - 1 ? 'text-right' : '' }}">{{ $headerLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @elseif(isset($headers))
                    <thead class="bg-table-head text-h border-b-table-head text-xs uppercase text-wider font-bold">
                        <tr>
                            {{ $headers }}
                        </tr>
                    </thead>
                @endif

                @if($bareBody)
                    {{ $slot }}
                @else
                    <tbody class="text-sm text-p" {!! $tbodyAttributes ?? '' !!}>
                        @if(!empty($rows))
                            @foreach($rows as $row)
                                <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                    <td class="p-4 font-bold text-p">
                                        <span>{{ $row['label'] ?? '' }}</span>
                                        @if(!empty($row['hint']))
                                            <span class="block text-xs text-muted">{{ $row['hint'] }}</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-sm text-p">
                                        {{ $row['value'] ?? '' }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex flex-row flex-wrap items-center justify-end gap-sm">
                                            @if(!empty($row['editClick']))
                                                <x-button type="button"
                                                          variant="ghost"
                                                          size="sm"
                                                          icon="bi-pencil"
                                                          :tooltip="$row['editTooltip'] ?? lexicon('tooltip_common_Edit', 'Edit')"
                                                          @click="{!! $row['editClick'] !!}">
                                                    {{ lexicon('btn_common_Edit', 'Edit') }}
                                                </x-button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @elseif($slot->isNotEmpty())
                            {{ $slot }}
                        @else
                            <tr>
                                <td colspan="100" class="p-6 text-center text-muted font-italic">
                                    {{ $emptyText ?? lexicon('msg_common_NoRecordsFound', 'No records found.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @endif
            </table>
        </div>
@endswitch