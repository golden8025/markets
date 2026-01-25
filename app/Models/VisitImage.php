<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitImage extends Model
{
    protected $fillable = [
        'visit_id',
        'image',
    ];

    public function visit(){
        return $this->belongsTo(Visit::class);
    }
}
