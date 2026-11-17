{{-- resources/views/layouts/app.blade.php --}}
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

    <!-- Skin CSS (Ready to uncomment when building is complete) -->
    <!-- <link rel="stylesheet" href="{{ asset('skins/' . $currentSkin . '/css/theme.css') }}?v={{ $themeVersion }}"> -->

    <!-- Vite handles 3rd-party vendor styles & core CSS/JS -->
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <!-- Dynamic Skin Dependencies (Fonts, Icons, etc.) -->
    @if(!empty($activeSkin?->custom_head_injection))
        {!! $activeSkin->custom_head_injection !!}
    @endif
</head>
<body class="bg-body text-p font-base min-h-screen flex flex-col"
      data-swal-success="{{ lexicon('label_common_Success', 'Success!') }}">

    <div class="min-h-screen flex flex-col">
        @includeIf('layouts.partials.header')
        @includeFirst(['layouts.partials.table_of_contents', 'layouts.partials.navigation'])

        <main class="w-full max-w-container mx-auto px-container pt-6 pb-4 flex-grow mb-lg mt-lg">
            @yield('content')
        </main>
    </div>

    @includeIf('layouts.partials.footer')
    @includeIf('layouts.partials.alerts')

    @auth
        <x-modal type="messenger"
                 :myCharacters="$myCharacters ?? collect()"
                 :users="$users ?? collect()"
                 :characters="$characters ?? collect()"
                 :threads="$threads ?? collect()" />

        <x-modal type="folio"
                 :activeBooks="$activeBooks ?? collect()"
                 :allCharacters="$allCharacters ?? collect()"
                 :languageRatings="$languageRatings ?? collect()"
                 :sexRatings="$sexRatings ?? collect()"
                 :violenceRatings="$violenceRatings ?? collect()" />
    @endauth

    @guest
        <x-modal type="auth" />
    @endguest

    @stack('scripts')
</body>
</html>