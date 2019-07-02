<?php

namespace App\Helpers;

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
}
