<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PresenceService
{
    private const PRESENCE_PREFIX = 'user_online_';
    private const UNREAD_PREFIX = 'user_unread_count_';
    private const PRESENCE_TTL_SECONDS = 300; // 5 minutes

    /**
     * Update user online status.
     */
    public function updatePresence(int $userId): void
    {
        Cache::put(self::PRESENCE_PREFIX . $userId, true, self::PRESENCE_TTL_SECONDS);
    }

    /**
     * Check if user is online.
     */
    public function isOnline(int $userId): bool
    {
        return Cache::has(self::PRESENCE_PREFIX . $userId);
    }

    /**
     * Get unread count for user, using cache with fallback to DB.
     */
    public function getUnreadCount(int $userId, callable $fallback): int
    {
        return (int) Cache::remember(
            self::UNREAD_PREFIX . $userId,
            now()->addHours(1),
            $fallback
        );
    }

    /**
     * Increment unread count in cache.
     */
    public function incrementUnreadCount(int $userId): void
    {
        $key = self::UNREAD_PREFIX . $userId;
        if (Cache::has($key)) {
            Cache::increment($key);
        }
    }

    /**
     * Invalidate/Clear unread count cache.
     */
    public function clearUnreadCache(int $userId): void
    {
        Cache::forget(self::UNREAD_PREFIX . $userId);
    }
}
