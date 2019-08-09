<?php

namespace App\Helpers;

use Carbon\Carbon;

class Helper
{
    public static function generateApiToken()
    {
        $token = random_bytes(60);

        return hash('sha256', $token);
    }

    public static function makeIdTokeyMap ($rows)
    {
        $result = new \stdClass();
        foreach ($rows as $row) {
            $result->{$row->id} = $row->key;
        }
        return $result;
    }

    /**
     * @throws InvalidArgumentException if the $schedule_id is in invalid format
     */
    public static function scheduleIdToTimestamp($scheduleId)
    {
        if (is_null($scheduleId)) return null;

        $scheduleId = Carbon::createFromFormat('YmdH', $scheduleId)->format('Y-m-d H:i:s');
    }
}
