<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketStock extends Model
{
    protected $fillable = [
        'sales_point_id',
        'total_loaded_qty',
        'total_loaded_amount',
        'current_stock',
    ];

    public function point(){
        return $this->belongsTo(Market::class);
    }
}
