@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
    <h1 class="text-2xl font-bold">Utilisateurs</h1>

    <div class="bg-white border border-slate-200 rounded-2xl mt-6 overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left px-4 py-3">Nom</th>
                    <th class="text-left px-4 py-3">E-mail</th>
                    <th class="text-left px-4 py-3">Rôle</th>
                    <th class="text-left px-4 py-3">Tentatives</th>
                    <th class="text-left px-4 py-3">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->role?->slug ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $user->attempts_count }}</td>
                        <td class="px-4 py-3">{{ $user->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection