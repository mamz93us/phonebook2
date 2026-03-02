<?php

namespace App\Services;

use App\Jobs\SendNotificationEmailJob;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function notify(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $severity = 'info'
    ): Notification {
        $notification = Notification::create([
            'user_id'    => $userId,
            'type'       => $type,
            'severity'   => $severity,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'is_read'    => false,
            'created_at' => now(),
        ]);

        // Queue email if user has email notifications enabled
        $settings = NotificationSetting::forUser($userId);
        if ($settings->notify_email) {
            SendNotificationEmailJob::dispatch($notification)->afterCommit();
        }

        return $notification;
    }

    public function notifyRole(
        string $role,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $severity = 'info'
    ): void {
        $users = User::where('role', $role)->get();
        foreach ($users as $user) {
            $this->notify($user->id, $type, $title, $message, $link, $severity);
        }
    }

    public function notifyAdmins(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $severity = 'info'
    ): void {
        $users = User::whereIn('role', ['super_admin', 'admin'])->get();
        foreach ($users as $user) {
            $this->notify($user->id, $type, $title, $message, $link, $severity);
        }
    }

    public function markRead(int $notificationId, int $userId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['is_read' => true]);
    }

    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function getForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getLatestUnread(int $userId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
