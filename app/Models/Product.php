<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'price',
        
    ];


    // public function market()
    // {
    //     return $this->hasMany(Market::class, 'product_stock');
    // }
    public function markets()
    {
        return $this->belongsToMany(Market::class, 'product_stocks')
                    ->withPivot('qty');
    }

    public function stocks() {
        return $this->hasMany(ProductStock::class);
    }
}
