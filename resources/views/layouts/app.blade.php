<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @stack('meta')

    <!-- Lexicon-Integrated Book Title with Fallback -->
    <title>@hasSection('title') @yield('title') - {{ config('app.name', 'MOWS') }} @else {{ config('app.name', 'MOWS') }} @endif</title>

    @php
        $currentSkin = $activeSkin?->directory_name ?? ($skinSettings['active_skin'] ?? 'default');
        $themeCssPath = public_path('skins/' . $currentSkin . '/css/theme.css');
        $themeVersion = file_exists($themeCssPath) ? filemtime($themeCssPath) : time();
    @endphp

    <!-- Dynamic Skin Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('skins/' . $currentSkin . '/images/site/favicon.png') }}">
    
    <!-- Skin CSS (Currently Disabled) -->
    <!-- <link rel="stylesheet" href="{{ asset('skins/' . $currentSkin . '/css/theme.css') }}?v={{ $themeVersion }}"> -->

    @php
        $divisionColours = \Illuminate\Support\Facades\Cache::rememberForever('global_division_colours', function () { 
            return \App\Models\Division::all(['id', 'division_base_colour'])->toArray(); 
        });

        $divisionCss = collect($divisionColours)
            ->map(fn($div) => ".section-theme-{$div['id']} { --section-color: {$div['division_base_colour']}; }")
            ->implode("\n        ");
    @endphp

    @if(!empty($divisionCss))
        {!! "<style>\n        {$divisionCss}\n    </style>" !!}
    @endif

    <!-- Vite handles 3rd-party vendor styles & core JS -->
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
    
    <!-- Dynamic Skin Dependencies (Fonts, Icons, etc.) -->
    @if(!empty($activeSkin?->custom_head_injection))
        {!! $activeSkin->custom_head_injection !!}
    @endif
</head>
<body class="bg-body font-base text-p" data-swal-success="{{ lexicon('label_common_Success', 'Success!') }}">
    
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
            :myPersonas="$myPersonas ?? collect()"
            :users="$users ?? collect()"
            :personas="$personas ?? collect()"
            :threads="$threads ?? collect()" />

        <x-chapter-modal
            :activeBooks="$activeBooks ?? collect()"
            :allPersonas="$allPersonas ?? collect()"
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