<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalStats extends Model
{
    protected $table = 'view_global_statistics';
    public $timestamps = false;
    protected $guarded = [];
}
