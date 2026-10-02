@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
    <div class="mx-auto max-w-md bg-white border border-slate-200 rounded-2xl p-8 mt-8">
        <h1 class="text-2xl font-bold mb-1">Créer un compte candidat</h1>
        <p class="text-sm text-slate-500 mb-6">Commencez votre préparation au TestDaF digital.</p>

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1" for="name">Nom complet</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password">Mot de passe (min. 8 caractères)</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password_confirmation">Confirmer le mot de passe</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-white font-medium hover:bg-blue-700">
                Créer mon compte
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-500">
            Déjà inscrit ?
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Se connecter</a>
        </p>
    </div>
@endsection