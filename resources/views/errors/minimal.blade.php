<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('meta')

    <!-- Lexicon-Integrated Page Title with Fallback -->
    <title>@hasSection('title') @yield('title') - {{ config('app.name', 'MOWS') }} @else {{ config('app.name', 'MOWS') }} @endif</title>

    @php
        $currentSkin = $activeSkin?->folder_name ?? ($skinSettings['active_skin'] ?? 'default');
        $themeCssPath = public_path('skins/' . $currentSkin . '/css/theme.css');
        $themeVersion = file_exists($themeCssPath) ? filemtime($themeCssPath) : time();
    @endphp

    <!-- Dynamic Skin Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('skins/' . $currentSkin . '/images/site/favicon.png') }}">

    <!-- Skin CSS (Currently Disabled in Dev Mode) -->
    <!-- <link rel="stylesheet" href="{{ asset('skins/' . $currentSkin . '/css/theme.css') }}?v={{ $themeVersion }}"> -->

    <!-- Vite handles 3rd-party vendor styles & core CSS/JS -->
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <!-- Dynamic Skin Dependencies (Fonts, Icons, etc.) -->
    @if(!empty($activeSkin?->custom_head))
        {!! $activeSkin->custom_head !!}
    @endif
</head>
<body class="bg-body font-base text-p">
    <div class="min-h-screen flex flex-col items-center justify-center p-4" x-data>
        <div class="w-full max-w-lg mx-auto px-container text-center">
            @hasSection('content')
                @yield('content')
            @else
                <div class="bg-panel border border-panel rounded-panel shadow-panel flex flex-col overflow-hidden">
                    <div class="p-6 flex flex-row flex-wrap items-center justify-center gap-lg">
                        <span class="text-3xl font-bold text-accent pr-2">
                            @yield('code')
                        </span>
                        <div class="flex flex-col gap-xs text-left border-l-2 pl-2">
                            <span class="text-h font-bold text-lg">
                                @yield('message')
                            </span>
                            <a href="{{ url('/') }}"
                               class="text-accent text-sm hover-text-h transition-colors no-underline flex items-center gap-xs"
                               aria-label="{{ lexicon('tooltip_common_ReturnHome', 'Return to Landing Page') }}"
                               x-tooltip="'{{ addslashes(lexicon('tooltip_common_ReturnHome', 'Return to Landing Page')) }}'">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                <span>{{ lexicon('btn_common_ReturnToHome', 'Return to Home') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>