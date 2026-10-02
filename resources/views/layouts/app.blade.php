<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Préparation TestDaF digital') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 flex items-center justify-between h-16">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg text-blue-700">
                TestDaF <span class="text-slate-400 font-normal">· digital</span>
            </a>

            @auth
                <nav class="hidden md:flex items-center gap-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('dashboard') ? 'bg-slate-100 font-semibold' : '' }}">Tableau de bord</a>
                    <a href="{{ route('modelltests.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('modelltests.*') ? 'bg-slate-100 font-semibold' : '' }}">Modelltests</a>
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-lg hover:bg-slate-100">Préparation ▾</button>
                        <div class="absolute hidden group-hover:block bg-white border border-slate-200 rounded-lg shadow-lg py-1 w-40 z-20">
                            @foreach (\App\Enums\Skill::sequence() as $skill)
                                <a href="{{ route('preparation.show', $skill->value) }}" class="block px-4 py-2 hover:bg-slate-100">{{ $skill->label() }}</a>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('notifications.*') ? 'bg-slate-100 font-semibold' : '' }}">Notifications</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-lg hover:bg-slate-100 {{ request()->routeIs('admin.*') ? 'bg-slate-100 font-semibold' : '' }}">Administration</a>
                    @endif
                </nav>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500 hidden sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-100">Déconnexion</button>
                    </form>
                </div>
            @endauth
        </div>
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