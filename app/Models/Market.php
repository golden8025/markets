<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'group_id',
        'name',
        'key',
        'type',
        'latitude',
        'longitude',
    ];

    public function group(){
        return $this->belongsTo(Group::class);
    }


    public function stock(){
        return $this->hasMany(ProductStock::class);
    }

    public function products(){
        return $this->hasMany(Product::class, 'product_stock');
    }

    public function users(){
        return $this->hasMany(User::class, 'market_user');
    }
}
