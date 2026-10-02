@extends('layouts.app')

@section('title', 'Préparation')

@section('content')
    <h1 class="text-2xl font-bold">Préparation</h1>
    <p class="text-sm text-slate-500 mb-6">Réviser par compétence, puis passer aux Modelltests.</p>

    <div class="grid md:grid-cols-2 gap-4">
        <a href="{{ route('notifications.index') }}" class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-blue-300">
            <div class="font-semibold">Mes notifications</div>
            <div class="text-sm text-slate-500 mt-1">Corrections disponibles, analyses IA terminées, nouveaux contenus.</div>
        </a>
        <a href="{{ route('modelltests.index') }}" class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-blue-300">
            <div class="font-semibold">Modelltests</div>
            <div class="text-sm text-slate-500 mt-1">Reproduire les conditions d'un examen numérique.</div>
        </a>
    </div>
@endsection