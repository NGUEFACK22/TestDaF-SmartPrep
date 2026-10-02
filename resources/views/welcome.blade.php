@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
    <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white rounded-3xl p-10 mt-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-3">Préparez le TestDaF digital</h1>
        <p class="max-w-2xl text-blue-100 mb-8">
            Une plateforme d'entraînement complète : Lesen, Hören, Schreiben, Sprechen — avec chronométrage
            individuel par tâche, verrouillage séquentiel, correction automatique, analyse IA indicative et
            statistiques de progression.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="rounded-lg bg-white text-blue-700 px-5 py-2.5 font-medium hover:bg-blue-50">
                Commencer
            </a>
            <a href="{{ route('login') }}" class="rounded-lg border border-blue-300/60 px-5 py-2.5 font-medium hover:bg-blue-700/40">
                J'ai déjà un compte
            </a>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mt-8">
        @foreach (\App\Enums\Skill::sequence() as $skill)
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-sm text-slate-400">Kompetenz</div>
                <div class="text-xl font-semibold">{{ $skill->label() }}</div>
                <p class="text-sm text-slate-500 mt-2">
                    Entraînement par type d'exercice et Modelltests complets.
                </p>
            </div>
        @endforeach
    </div>

    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl p-5 mt-8 text-sm">
        <strong>Important :</strong> l'évaluation IA et les scores de la plateforme sont
        <em>pédagogiques et indicatifs</em> ; ils ne constituent pas une note officielle du TestDaF.
    </div>
@endsection