<?php

namespace App\Helpers;

class Helper
{
    public static function generateApiToken()
    {
        $token = random_bytes(60);

        return hash('sha256', $token);
    }
}
