<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonResult extends Model
{
    protected $table = 'salmon_results';
    protected $hidden = [];
    protected $casts = [
        'members' => 'array',
    ];

    public function waves()
    {
        return $this
            ->hasMany('App\SalmonWave', 'salmon_id')
            ->with(['event', 'water'])
            ->orderBy('wave');
    }
}
