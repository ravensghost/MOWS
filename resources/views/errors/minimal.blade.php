{{-- resources/views/errors/minimal.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @stack('meta')

    <title>@hasSection('title') @yield('title') - {{ config('app.name', 'MOWS') }} @else {{ config('app.name', 'MOWS') }} @endif</title>

    @php
        $currentSkin = $activeSkin?->directory_name ?? ($skinSettings['active_skin'] ?? 'default');
    @endphp

    <link rel="icon" type="image/png" href="{{ asset('skins/' . $currentSkin . '/images/site/favicon.png') }}">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    @if(!empty($activeSkin?->custom_head_injection))
        {!! $activeSkin->custom_head_injection !!}
    @endif
</head>
<body class="bg-body text-p font-base min-h-screen flex flex-col items-center justify-center p-6">
    <div class="w-full max-w-lg mx-auto">
        <x-tab type="panel"
               :title="trim($__env->yieldContent('code')) . ' — ' . trim($__env->yieldContent('title'))"
               icon="bi-exclamation-octagon">
            <div class="flex flex-col items-center justify-center gap-md text-center py-1">
                <span class="text-4xl font-bold text-accent font-mono">@yield('code')</span>
                <p class="text-sm text-p m-0">@yield('message')</p>

                <div class="border-t-actions pt-4 w-full flex flex-row items-center justify-center">
                    <x-button variant="primary"
                              icon="bi-house-door"
                              href="/"
                              :tooltip="lexicon('tooltip_common_ReturnHome', 'Return to Home Page')">
                        {{ lexicon('btn_common_ReturnHome', 'Return Home') }}
                    </x-button>
                </div>
            </div>
        </x-tab>
    </div>
</body>
</html>