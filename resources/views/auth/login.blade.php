@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <div class="mx-auto max-w-md bg-white border border-slate-200 rounded-2xl p-8 mt-8">
        <h1 class="text-2xl font-bold mb-1">Connexion</h1>
        <p class="text-sm text-slate-500 mb-6">Accédez à votre espace de préparation au TestDaF digital.</p>

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1" for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Se souvenir de moi
            </label>

            <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-white font-medium hover:bg-blue-700">
                Se connecter
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-500">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Créer un compte</a>
        </p>
    </div>
@endsection