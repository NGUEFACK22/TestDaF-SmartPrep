@extends('layouts.app')

@section('title', 'Mon compte')

@section('content')
    <h1 class="text-2xl font-bold">Mon compte</h1>
    <p class="text-sm text-slate-500 mt-1">{{ $user->name }} · {{ $user->email }} · {{ $user->attempts_count }} tentative(s)</p>

    <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="font-semibold mb-2">Exporter mes données (RGPD)</h2>
            <p class="text-sm text-slate-500 mb-4">Profil, tentatives, résultats et recommandations au format JSON.</p>
            <a href="{{ route('account.export') }}" class="btn rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">Télécharger l'export JSON</a>
        </div>

        <div class="bg-white border border-red-200 rounded-2xl p-5">
            <h2 class="font-semibold mb-2 text-red-700">Supprimer mon compte</h2>
            <p class="text-sm text-slate-500 mb-4">Définitif : tentatives, réponses et recommandations sont effacés. Cette action est irréversible.</p>
            <form method="POST" action="{{ route('account.destroy') }}" class="space-y-3" onsubmit="return confirm('Supprimer définitivement votre compte et toutes vos données ?');">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-sm font-medium mb-1" for="password">Mot de passe (confirmation)</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-red-500 focus:ring-red-500">
                    @error('password')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="confirm" value="1" required class="rounded border-slate-300">
                    Je confirme la suppression définitive
                </label>
                <button class="btn rounded-lg bg-red-600 text-white px-4 py-2 text-sm font-medium hover:bg-red-700">
                    Supprimer définitivement
                </button>
            </form>
        </div>
    </div>
@endsection
