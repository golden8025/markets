<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $fillable = [
        'product_id',
        'market_id',
        'qty',
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function market(){
        return $this->belongsTo(Market::class);
    }
}
