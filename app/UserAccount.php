<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserAccount extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'user_accounts';

    protected $primaryKey = null;
    protected $guarded = [];
    protected $hidden = [];
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function name()
    {
        return $this->hasOne('App\SalmonPlayerName', 'player_id', 'player_id');
    }
}
