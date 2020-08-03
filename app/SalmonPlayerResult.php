<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonPlayerResult extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $table = 'salmon_player_results';
    protected $hidden = ['salmon_id'];
    protected $guarded = [];
    public $timestamps = false;

    public function weapons()
    {
        return $this
            ->hasMany(
                'App\SalmonPlayerWeapon',
                ['salmon_id', 'player_id'],
                ['salmon_id', 'player_id']
            )
            ->orderBy('wave');
    }
}
