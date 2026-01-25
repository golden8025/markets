<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalStat extends Model
{
    protected $table = 'view_global_statistics';
    public $timestamps = false;
    protected $guarded = [];
}
