@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Notifications ({{ $unread }} non lue(s))</h1>
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Tout marquer comme lu</button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6 space-y-3">
        @forelse ($notifications as $notification)
            <div class="flex items-center justify-between border-b border-slate-100 last:border-0 py-2 {{ $notification->read_at ? 'opacity-60' : '' }}">
                <div>
                    <div class="text-sm font-medium">{{ $notification->data['message'] ?? $notification->type }}</div>
                    <div class="text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
                <div class="flex items-center gap-2">
                    @if (! empty($notification->data['url']))
                        <a href="{{ $notification->data['url'] }}" class="text-sm text-blue-600 hover:underline">Voir</a>
                    @endif
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button class="text-sm text-slate-500 hover:underline">Marquer comme lu</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Aucune notification.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
@endsection