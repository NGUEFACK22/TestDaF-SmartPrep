@extends('layouts.app')

@section('title', 'Paramètres IA')

@section('content')
    <h1 class="text-2xl font-bold">Paramètres IA</h1>
    <p class="text-sm text-slate-500 mb-6">Coûts, quotas et comportements de l'évaluation automatique.</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-2xl space-y-4">
        @csrf
        @method('PUT')

        <label class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl p-4 text-sm">
            <input type="checkbox" name="ai_enabled" value="1" @checked($settings['ai_enabled']) class="h-4 w-4">
            <span><strong>Activer l'analyse IA</strong><br>
            <span class="text-slate-500">Sans clé API, les analyses restent « en attente » et ré-essayables.</span></span>
        </label>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Fournisseur</label>
                <input name="ai_provider" value="{{ $settings['ai_provider'] }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Modèle</label>
                <input name="ai_model" value="{{ $settings['ai_model'] }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Quota maximal de requêtes IA</label>
            <input name="max_ai_requests" type="number" min="0" value="{{ $settings['max_ai_requests'] }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>

        <label class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl p-4 text-sm">
            <input type="checkbox" name="ai_auto_evaluation" value="1" @checked($settings['ai_auto_evaluation']) class="h-4 w-4">
            <span><strong>Évaluation automatique</strong><br>
            <span class="text-slate-500">Lance l'analyse IA dès la validation d'une production.</span></span>
        </label>

        <label class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl p-4 text-sm">
            <input type="checkbox" name="ai_manual_review" value="1" @checked($settings['ai_manual_review']) class="h-4 w-4">
            <span><strong>Revue manuelle possible</strong><br>
            <span class="text-slate-500">Permet au correcteur de valider / corriger les productions.</span></span>
        </label>

        <button class="btn rounded-lg bg-blue-600 text-white px-5 py-2.5 font-medium hover:bg-blue-700">Enregistrer</button>
    </form>
@endsection