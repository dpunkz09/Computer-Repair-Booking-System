<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()
            ->userNotifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'unread_count' => Auth::user()->unreadNotificationsCount(),
            'notifications' => $notifications->map(fn (UserNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'url' => $n->actionUrl(),
                'read' => ! $n->isUnread(),
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(UserNotification $userNotification)
    {
        abort_unless($userNotification->user_id === Auth::id(), 403);

        $userNotification->markAsRead();

        if ($url = $userNotification->actionUrl()) {
            return redirect($url);
        }

        return back();
    }

    public function markAllRead(Request $request)
    {
        Auth::user()
            ->userNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'All notifications marked as read.']);
        }

        return back();
    }
}
