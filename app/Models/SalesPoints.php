<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPoints extends Model
{
    protected $fillable = [
        
    ];

    public function group(){
        return $this->belongsTo(Group::class);
    }

    
}
