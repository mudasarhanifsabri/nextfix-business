<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $latest = $request->user()->notifications()->latest()->first();
        $title = $latest?->data['title'] ?? 'Notification';
        $url = $latest?->data['url'] ?? '';
        $sound = $request->user()->role === 'professional' && $title === 'New job assigned'
            ? 'job'
            : (str_contains($url, '/chat') ? 'message' : 'default');

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'latest' => $latest ? [
                'id' => $latest->id,
                'title' => $title,
                'message' => $latest->data['message'] ?? '',
                'level' => $latest->data['level'] ?? 'info',
                'url' => route('admin.notifications.read', $latest),
                'sound' => $sound,
                'created_at' => $latest->created_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function index(Request $request): View
    {
        return view('admin.notifications', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return redirect($item->data['url'] ?? route('admin.notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
