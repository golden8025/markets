<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        
    ];


    public function market()
    {
        return $this->hasMany(Market::class, 'product_stock');
    }
    
}
