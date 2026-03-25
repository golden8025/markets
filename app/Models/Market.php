<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'key',
        'type',
        'latitude',
        'longitude',
    ];

    // protected $casts = ['type'];
    public function group(){
        return $this->belongsTo(Group::class);
    }

    public function products()
    {
        
        return $this->belongsToMany(Product::class, 'product_stocks')
                    ->withPivot('qty');
    }

    public function stocks() 
    {
        return $this->hasMany(ProductStock::class);
    }

    public function users(){
        return $this->belongsToMany(User::class, 'market_users');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }


    public function latestVisit()
    {
        return $this->hasOne(Visit::class)->latestOfMany();
    }
}
