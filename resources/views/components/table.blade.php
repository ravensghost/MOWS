@props([
    'tbodyAttributes' => null,
    'bareBody' => false,
])

<div {{ $attributes->merge(['class' => 'table-wrapper overflow-y-auto w-full']) }}>
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