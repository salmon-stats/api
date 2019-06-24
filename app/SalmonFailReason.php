<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonFailReason extends Model
{
    protected $table = 'salmon_fail_reasons';
    protected $visible = ['id', 'key'];
}
