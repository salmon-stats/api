<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    public static function purgePlayerCaches(string $playerId)
    {
        $purgedCacheCount = 0;

        $cacheKeyPrefixes = [
            'players.metadata',
            'players.weapons',
        ];

        foreach ($cacheKeyPrefixes as $cacheKeyPrefix) {
            $key = "$cacheKeyPrefix.$playerId";
            $purgedCacheCount += Cache::forget($key) ? 1 : 0;
        }

        \Log::debug("Purged $purgedCacheCount cache(s) for $playerId.");
    }
}
