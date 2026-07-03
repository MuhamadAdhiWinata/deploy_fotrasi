<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Fortasi') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-primary min-h-screen flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-md bg-white border-4 border-dark shadow-[8px_8px_0px_0px_#1a1a1a] p-6">
        <div class="text-center mb-6">
            <div class="inline-block bg-highlight border-4 border-dark px-4 py-2 -rotate-1 mb-3">
                <h1 class="text-2xl font-extrabold text-dark uppercase tracking-tight">FORTASI</h1>
            </div>
            <p class="text-sm font-semibold text-dark/70">Forum Ta'aruf dan Orientasi</p>
        </div>
        {{ $slot }}
    </div>
</body>
</html>
