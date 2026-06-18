<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * The app has no authentication, so failure notifications are delivered to a
 * single "system" account via Laravel's database notification channel. The
 * dashboard reads this feed to show an in-app notification bell.
 */
class SystemNotifier
{
    /** Get (or lazily create) the system notifiable account. */
    public static function account(): User
    {
        return User::firstOrCreate(
            ['email' => 'system@import.local'],
            ['name' => 'System', 'password' => bcrypt(Str::random(40))],
        );
    }

    public static function send(Notification $notification): void
    {
        self::account()->notify($notification);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recent(int $limit = 10): array
    {
        return self::account()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ])
            ->all();
    }

    public static function unreadCount(): int
    {
        return self::account()->unreadNotifications()->count();
    }

    public static function markAllRead(): void
    {
        self::account()->unreadNotifications->markAsRead();
    }
}
