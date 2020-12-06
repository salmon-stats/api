<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        return $this->hasMany('App\UserAccount', 'user_id')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('player_id');
    }

    /**
     * Returns player page if the user has uploaded result at least once.
     * Otherwise returns null.
     */
    public function getPlayerPage()
    {
        $account = \App\UserAccount::where('user_id', $this->id)->first();
        if (!isset($account)) return null;

        return env('APP_FRONTEND_ORIGIN') . "/players/$account->player_id";
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

        return $this->getRawOriginal('name');
    }

    public function getTwitterAvatarAttribute()
    {
        if ($this->show_twitter_avatar) {
            return $this->getRawOriginal('twitter_avatar');
        }

        return null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }
}
