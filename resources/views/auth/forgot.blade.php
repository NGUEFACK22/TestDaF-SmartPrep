@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
    <div class="mx-auto max-w-md bg-white border border-slate-200 rounded-2xl p-8 mt-8">
        <h1 class="text-2xl font-bold mb-1">Mot de passe oublié</h1>
        <p class="text-sm text-slate-500 mb-6">Recevez un lien de réinitialisation par e-mail.</p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1" for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-white font-medium hover:bg-blue-700">
                Envoyer le lien
            </button>
        </form>

        <p class="mt-6 text-sm text-slate-500">
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Retour à la connexion</a>
        </p>
    </div>
@endsection
