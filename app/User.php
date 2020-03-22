<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    protected $appends = ['is_custom_name', 'is_registered'];

    protected $casts = [
        'show_twitter_avatar' => 'boolean',
    ];

    protected $rememberTokenName = false;

    protected $fillable = [
        'name', 'twitter_id', 'api_token', 'twitter_avatar', 'display_name', 'show_twitter_avatar',
    ];

    protected $hidden = [
        'api_token', 'created_at', 'twitter_id', 'display_name', 'show_twitter_avatar',
    ];

    public function accounts()
    {
        return $this->hasMany('App\UserAccount', 'user_id');
    }

    /**
     * Returns player page if the user has uploaded result at least once.
     * Otherwise returns null.
     */
    public function getPlayerPage()
    {
        if ($this->player_id) return env('APP_FRONTEND_ORIGIN') . "/players/{$this->player_id}";
        return null;
    }

    public function getIsCustomNameAttribute()
    {
        return !is_null($this->display_name);
    }

    public function getIsRegisteredAttribute()
    {
        return !is_null($this->name);
    }

    public function getNameAttribute()
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        return $this->getOriginal('name');
    }

    public function getTwitterAvatarAttribute()
    {
        if ($this->show_twitter_avatar) {
            return $this->getOriginal('twitter_avatar');
        }

        return null;
    }
}
