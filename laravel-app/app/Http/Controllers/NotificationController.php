<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function markAllRead(): RedirectResponse
    {
        if (Schema::hasTable('notifications')) {
            auth()->user()?->unreadNotifications->markAsRead();
        }

        return back()->with('success', 'Notifikasi sudah ditandai dibaca.');
    }
}
