<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class SalmonWave extends Model
{
    protected $table = 'salmon_waves';
    protected $hidden = ['salmon_id'];
    // protected $hidden = ['salmon_id', 'event_id', 'water_id'];
    protected $guarded = [];
    public $timestamps = false;

    public function event()
    {
        return $this->hasOne('App\SalmonEvent', 'id', 'event_id');
    }

    public function water()
    {
        return $this->hasOne('App\SalmonWaterLevel', 'id', 'water_id');
    }
}
