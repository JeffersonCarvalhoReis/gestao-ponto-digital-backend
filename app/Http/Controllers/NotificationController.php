<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function allUnreadNotifications() {

        return [
            'notifications' => auth()->user()->unreadNotifications
        ];
    }

    public function readNotification (string $id) {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function readAllNotifications () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllAsReadByStatus(Request $request) {
        $request->validate([
            'status' => 'required|in:aprovado,recusado,pendente'
        ]);

        $user = auth()->user();
        $notifications = $user->unreadNotifications->where('data.status', $request->status);

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notificações marcadas como lidas']);
    }

}
