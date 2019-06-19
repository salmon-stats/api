<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $rememberTokenName = false;

    protected $fillable = [
        'name', 'twitter_id',
    ];

    protected $hidden = [
        'api_key', 'twitter_id',
    ];
}
