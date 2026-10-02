@extends('layouts.app')

@section('title', 'Exercices')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Bibliothèque d'exercices</h1>
        <a href="{{ route('admin.exercises.create') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">Créer</a>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left px-4 py-3">Compétence</th>
                    <th class="text-left px-4 py-3">Titre</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Questions</th>
                    <th class="text-left px-4 py-3">Statut</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($exercises as $exercise)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $exercise->skill->label() }}</td>
                        <td class="px-4 py-3 font-medium">{{ $exercise->title }}</td>
                        <td class="px-4 py-3">{{ $exercise->type }}</td>
                        <td class="px-4 py-3">{{ $exercise->questions_count }}</td>
                        <td class="px-4 py-3">{{ $exercise->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.exercises.edit', $exercise) }}" class="text-blue-600 hover:underline">Modifier</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $exercises->links() }}</div>
@endsection