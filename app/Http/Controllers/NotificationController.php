<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Notifications Updated',
            'message' => 'All notifications were marked as read.',
        ]);
    }
}
