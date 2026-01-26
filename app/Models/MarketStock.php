<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketStock extends Model
{
    use HasFactory;
    protected $fillable = [
        'market_id',
        'total_loaded_qty',
        'total_loaded_amount',
        'current_stock',
    ];

    public function point(){
        return $this->belongsTo(Market::class);
    }
}
