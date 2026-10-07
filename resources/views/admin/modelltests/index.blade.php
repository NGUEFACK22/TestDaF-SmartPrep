@extends('layouts.app')

@section('title', 'Modelltests')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Modelltests</h1>
        <a href="{{ route('admin.modelltests.create') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">Créer</a>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl mt-6 overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left px-4 py-3">N°</th>
                    <th class="text-left px-4 py-3">Titre</th>
                    <th class="text-left px-4 py-3">Sections</th>
                    <th class="text-left px-4 py-3">Statut</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($modelltests as $modelltest)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $modelltest->number }}</td>
                        <td class="px-4 py-3 font-medium">{{ $modelltest->title }}</td>
                        <td class="px-4 py-3">{{ $modelltest->sections_count }}</td>
                        <td class="px-4 py-3">{{ $modelltest->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.modelltests.edit', $modelltest) }}" class="text-blue-600 hover:underline">Modifier</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $modelltests->links() }}</div>
@endsection