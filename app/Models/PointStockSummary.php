<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointStockSummary extends Model
{
    protected $fillable = [
        
    ];

    public function point(){
        return $this->belongsTo(SalesPoints::class);
    }
}
