<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'MOWS Engine Setup Wizard')</title>

    @php
        $activeSetupSkin = $selectedTheme ?? 'default';
    @endphp

    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/visual.css') }}">
    <link rel="stylesheet" href="{{ asset("skins/{$activeSetupSkin}/css/theme.css") }}">
</head>
<body class="bg-base text-body font-sans antialiased">
    <main class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-4xl flex flex-col gap-lg">
            @yield('content')
        </div>
    </main>

    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>