@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
    <div class="mx-auto max-w-md bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 mt-4 sm:mt-8">
        <h1 class="text-2xl font-bold mb-1">Nouveau mot de passe</h1>
        <p class="text-sm text-slate-500 mb-6">Choisissez un mot de passe d'au moins 8 caractères.</p>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-sm font-medium mb-1" for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-white font-medium hover:bg-blue-700">
                Réinitialiser
            </button>
        </form>
    </div>
@endsection
