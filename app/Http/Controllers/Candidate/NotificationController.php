<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('candidate.notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(25),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back()->with('status', 'Notification marquée comme lue.');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications ont été marquées comme lues.');
    }
}
