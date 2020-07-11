<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonSchedule extends Model
{
    protected $table = 'salmon_schedules';
    protected $hidden = [];
    protected $casts = [
        'weapons' => 'array',
    ];
    protected $primaryKey = 'schedule_id';
    public $incrementing = false;
    public $timestamps = false;
}
