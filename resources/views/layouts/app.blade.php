<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap&subset=latin-ext" rel="stylesheet">
    <title>@yield('title', 'Préparation TestDaF digital') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 flex items-center justify-between h-16">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg text-blue-700">
                SYNPHONIE <span class="text-slate-400 font-normal">· TestDaF digital</span>
            </a>

            @auth
                <nav class="hidden md:flex items-center gap-1 text-sm" aria-label="Navigation principale">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-slate-100 font-semibold' : '' }}">Tableau de bord</a>
                    <a href="{{ route('preparation.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('preparation.*') ? 'bg-slate-100 font-semibold' : '' }}">Niveaux</a>
                    <a href="{{ route('challenges.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('challenges.*') ? 'bg-slate-100 font-semibold' : '' }}">Défis IA 🏆</a>
                    <a href="{{ route('notifications.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('notifications.*') ? 'bg-slate-100 font-semibold' : '' }}">Notifications</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('admin.*') ? 'bg-slate-100 font-semibold' : '' }}">Administration</a>
                    @endif
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="text-sm text-slate-500 hidden sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button class="text-sm px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-100">Déconnexion</button>
                    </form>
                    <button type="button" id="nav-toggle" class="md:hidden p-2 rounded-lg hover:bg-slate-100" aria-expanded="false" aria-controls="mobile-menu" aria-label="Ouvrir le menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                </div>
            @endauth
        </div>
        @auth
            <nav id="mobile-menu" class="menu-hidden md:hidden border-t border-slate-200 px-4 py-3 space-y-1 text-sm" aria-label="Navigation mobile">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2.5 rounded-lg hover:bg-slate-100">Tableau de bord</a>
                <a href="{{ route('preparation.index') }}" class="block px-3 py-2.5 rounded-lg hover:bg-slate-100">Niveaux</a>
                <a href="{{ route('challenges.index') }}" class="block px-3 py-2.5 rounded-lg hover:bg-slate-100">Défis IA 🏆</a>
                <a href="{{ route('notifications.index') }}" class="block px-3 py-2.5 rounded-lg hover:bg-slate-100">Notifications</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2.5 rounded-lg hover:bg-slate-100">Administration</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full text-left px-3 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-100 mt-1">Déconnexion ({{ auth()->user()->name }})</button>
                </form>
            </nav>
        @endauth
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 mt-12">
        <div class="mx-auto max-w-7xl px-4 py-6 text-xs text-slate-400">
            Évaluation pédagogique à titre d'entraînement — ne constitue pas un résultat officiel du TestDaF.
        </div>
    </footer>
</body>
</html>