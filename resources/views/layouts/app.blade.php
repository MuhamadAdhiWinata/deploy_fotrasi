<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo_mupa.png') }}">
    <title>{{ config('app.name', 'Fortasi') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-surface {{ auth()->user()->isAdmin() ? '' : 'pb-20 md:pb-0' }}">
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    @if ($isAdmin)
        {{-- Mobile Header --}}
        <header class="md:hidden bg-white border-b-4 border-dark px-4 py-3 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-2">
                <div class="bg-highlight border-3 border-dark px-2 py-0.5">
                    <span class="font-extrabold text-dark">F</span>
                </div>
                <span class="font-extrabold text-sm uppercase">Fortasi Admin</span>
            </div>
            <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="p-2 border-3 border-dark">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </header>

        {{-- Mobile Menu --}}
        <div id="mobileMenu" class="hidden md:hidden fixed inset-0 z-50 bg-dark/50">
            <div class="absolute right-0 top-0 bottom-0 w-72 bg-white border-l-4 border-dark p-4 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <span class="font-extrabold text-sm uppercase">Menu</span>
                    <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="p-2 border-3 border-dark">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="space-y-1">
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" wire:navigate>Dashboard</x-nav-link>
                    <x-nav-link :href="route('admin.siswa')" :active="request()->routeIs('admin.siswa*')" wire:navigate>Siswa</x-nav-link>
                    <x-nav-link :href="route('admin.presensi')" :active="request()->routeIs('admin.presensi*')" wire:navigate>Presensi</x-nav-link>
                    <x-nav-link :href="route('admin.tugas')" :active="request()->routeIs('admin.tugas*')" wire:navigate>Tugas</x-nav-link>
                    <x-nav-link :href="route('admin.periode')" :active="request()->routeIs('admin.periode*')" wire:navigate>Periode</x-nav-link>
                    <x-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>Profile</x-nav-link>
                </nav>
                <div class="mt-6 pt-6 border-t-4 border-dark">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</div>
                        <div>
                            <p class="text-sm font-bold">{{ auth()->user()->name }}</p>
                            <p class="text-xs font-semibold text-dark/60 uppercase">Admin</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full bg-red-500 text-white border-3 border-dark py-2 font-bold text-sm uppercase shadow-[4px_4px_0px_0px_#1a1a1a] hover:translate-x-0.5 hover:translate-y-0.5 transition-all">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Desktop Sidebar --}}
        <aside class="hidden md:flex md:fixed md:inset-y-0 md:left-0 md:w-64 md:flex-col bg-white border-r-4 border-dark z-30">
            <div class="flex items-center gap-3 px-6 py-5 border-b-4 border-dark">
                <div class="bg-highlight border-3 border-dark px-2 py-1">
                    <span class="font-extrabold text-dark text-lg">F</span>
                </div>
                <div>
                    <h2 class="font-extrabold text-dark uppercase text-sm leading-tight">Fortasi</h2>
                    <p class="text-[10px] font-semibold text-dark/60">Admin Panel</p>
                </div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" wire:navigate>Dashboard</x-nav-link>
                <x-nav-link :href="route('admin.siswa')" :active="request()->routeIs('admin.siswa*')" wire:navigate>Siswa</x-nav-link>
                <x-nav-link :href="route('admin.presensi')" :active="request()->routeIs('admin.presensi*')" wire:navigate>Presensi</x-nav-link>
                <x-nav-link :href="route('admin.tugas')" :active="request()->routeIs('admin.tugas*')" wire:navigate>Tugas</x-nav-link>
                <x-nav-link :href="route('admin.periode')" :active="request()->routeIs('admin.periode*')" wire:navigate>Periode</x-nav-link>
                <x-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>Profile</x-nav-link>
            </nav>
            <div class="p-4 border-t-4 border-dark">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold truncate text-dark">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-semibold text-dark/60 uppercase">Admin</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-dark/50 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    @else
        {{-- Siswa Bottom Nav --}}
        <nav class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white border-t-4 border-dark shadow-[0_-4px_0px_0px_#1a1a1a]">
            <div class="flex items-center justify-around h-16">
                <a href="{{ route('siswa.dashboard') }}" wire:navigate class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('siswa.dashboard') ? 'text-primary' : 'text-dark/50' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-[10px] font-bold uppercase">Beranda</span>
                </a>
                <a href="{{ route('siswa.todo') }}" wire:navigate class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('siswa.todo*') ? 'text-primary' : 'text-dark/50' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="text-[10px] font-bold uppercase">To Do</span>
                </a>
                <a href="{{ route('profile') }}" wire:navigate class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('profile') ? 'text-primary' : 'text-dark/50' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-[10px] font-bold uppercase">Profile</span>
                </a>
            </div>
        </nav>

        {{-- Siswa Desktop Sidebar --}}
        <aside class="hidden md:flex md:fixed md:inset-y-0 md:left-0 md:w-64 md:flex-col bg-white border-r-4 border-dark z-30">
            <div class="flex items-center gap-3 px-6 py-5 border-b-4 border-dark">
                <div class="bg-highlight border-3 border-dark px-2 py-1">
                    <span class="font-extrabold text-dark text-lg">F</span>
                </div>
                <div>
                    <h2 class="font-extrabold text-dark uppercase text-sm leading-tight">Fortasi</h2>
                    <p class="text-[10px] font-semibold text-dark/60">Forum Ta'aruf dan Orientasi</p>
                </div>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <x-nav-link :href="route('siswa.dashboard')" :active="request()->routeIs('siswa.dashboard')" wire:navigate>Beranda</x-nav-link>
                <x-nav-link :href="route('siswa.todo')" :active="request()->routeIs('siswa.todo*')" wire:navigate>To Do</x-nav-link>
                <x-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>Profile</x-nav-link>
            </nav>
            <div class="p-4 border-t-4 border-dark">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 bg-secondary border-3 border-dark flex items-center justify-center text-white font-extrabold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold truncate text-dark">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-semibold text-dark/60 uppercase">{{ auth()->user()->nis ?? 'Siswa' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-dark/50 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    @endif

    {{-- Content --}}
    <div class="{{ $isAdmin ? 'md:ml-64' : 'md:ml-64' }}">
        @if (isset($header))
            <div class="p-4 md:p-8 pb-0 max-w-7xl mx-auto">
                {{ $header }}
            </div>
        @endif
        <main class="p-4 md:p-8 max-w-7xl mx-auto">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
