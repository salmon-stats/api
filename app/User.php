<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

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

    /**
     * Returns player page if the user has uploaded result at least once.
     * Otherwise returns null.
     */
    public function getPlayerPage()
    {
        if ($this->player_id) return env('APP_FRONTEND_ORIGIN') . "/players/{$this->player_id}";
        return null;
    }

    public function getNameAttribute()
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        return $this->name;
    }

    public function getTwitterAvatarAttribute()
    {
        if ($this->show_twitter_avatar) {
            return $this->getOriginal('twitter_avatar');
        }

        return null;
    }

    public static function getRawSelectQuery($useSalmonPlayerNames = false)
    {
        if ($useSalmonPlayerNames) {
            return [
                DB::raw('COALESCE(users.display_name, users.name, salmon_player_names.name) AS name'),
                DB::raw('CASE WHEN users.name IS NULL THEN FALSE ELSE TRUE END AS is_registered'),
                DB::raw('users.display_name IS NOT NULL AS is_custom_name'),
                DB::raw('CASE WHEN show_twitter_avatar = 1 THEN users.twitter_avatar ELSE NULL END AS twitter_avatar'),
                'salmon_player_names.player_id AS player_id',
            ];
        }

        return [
            DB::raw('COALESCE(users.display_name, users.name) AS name'),
            DB::raw('CASE WHEN users.name IS NULL THEN FALSE ELSE TRUE END AS is_registered'),
            DB::raw('users.display_name IS NOT NULL AS is_custom_name'),
            DB::raw('CASE WHEN show_twitter_avatar = 1 THEN users.twitter_avatar ELSE NULL END AS twitter_avatar'),
            'player_id',
        ];
    }
}
