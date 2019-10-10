<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $rememberTokenName = false;

    protected $fillable = [
        'name', 'twitter_id', 'api_token', 'twitter_avatar'
    ];

    protected $hidden = [
        'api_token', 'created_at', 'twitter_id',
    ];

    /**
     * Returns player page if the user has uploaded result at least once.
     * Otherwise returns null.
     */
    public function getPlayerPage()
    {
        if ($this->player_id) return env('APP_FRONTEND_ORIGIN') . "/players/{$this->player_id}";
        return null;
    }
}
