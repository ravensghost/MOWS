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
    
    <!-- Skin CSS (Currently Disabled) -->
    <!-- <link rel="stylesheet" href="{{ asset('skins/' . $currentSkin . '/css/theme.css') }}?v={{ $themeVersion }}"> -->

    @php
        $sectionColors = \Illuminate\Support\Facades\Cache::rememberForever('global_section_colors', function() { 
            return \App\Models\Section::all(['id', 'color'])->toArray(); 
        });

        $sectionCss = collect($sectionColors)
            ->map(fn($sec) => ".section-theme-{$sec['id']} { --section-color: {$sec['color']}; }")
            ->implode("\n        ");
    @endphp

    @if(!empty($sectionCss))
        {!! "<style>\n        {$sectionCss}\n    </style>" !!}
    @endif

    <!-- Vite handles 3rd-party vendor styles & core JS -->
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
    
    <div class="min-h-screen flex flex-col">
        @includeIf('layouts.partials.header')
        @includeIf('layouts.partials.navigation')

        <main class="w-full max-w-container mx-auto px-container pt-6 pb-4 flex-grow mb-lg mt-lg">
            @yield('content')
        </main>
    </div>

    @includeIf('layouts.partials.footer')
    @includeIf('layouts.partials.alerts')

    @auth
        <x-messenger-modal
            :myCharacters="$myCharacters ?? collect()"
            :users="$users ?? collect()"
            :characters="$characters ?? collect()"
            :threads="$threads ?? collect()" />

        <x-chapter-modal
            :activeBooks="$activeBooks ?? collect()"
            :allCharacters="$allCharacters ?? collect()"
            :languageRatings="$languageRatings ?? collect()"
            :sexRatings="$sexRatings ?? collect()"
            :violenceRatings="$violenceRatings ?? collect()" />
    @endauth

    @stack('scripts')
    @guest
        @include('layouts.partials.auth-modal')
    @endguest
</body>
</html>