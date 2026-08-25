<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with(['client', 'user'])->orderBy('created_at', 'desc');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(15)->withQueryString();
        $unreadCount = Notification::where('is_read', false)->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(Notification $notification)
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        Notification::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
