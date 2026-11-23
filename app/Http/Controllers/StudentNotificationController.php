<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20)->withQueryString(),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $notification->markAsRead();

        $actionUrl = $notification->data['action_url'] ?? null;
        $parsedActionUrl = is_string($actionUrl) ? parse_url($actionUrl) : false;

        if (
            ! is_string($actionUrl)
            || ! is_array($parsedActionUrl)
            || ! str_starts_with($actionUrl, '/')
            || str_starts_with($actionUrl, '//')
            || str_contains($actionUrl, '\\')
            || isset($parsedActionUrl['host'])
            || isset($parsedActionUrl['scheme'])
        ) {
            $actionUrl = route('dashboard', absolute: false);
        }

        return redirect($actionUrl);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}
