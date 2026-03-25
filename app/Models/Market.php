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


    // public function stock(){
    //     return $this->hasMany(ProductStock::class);
    // }

    // public function products(){
    //     return $this->hasMany(Product::class, 'product_stock');
    // }

    public function products()
    {
        // Указываем промежуточную таблицу 'product_stocks'
        // и можем подтянуть дополнительные поля, например 'qty'
        return $this->belongsToMany(Product::class, 'product_stocks')
                    ->withPivot('qty');
    }

    public function stocks() // Это связь "Один-ко-многим" к записям в таблице остатков
    {
        return $this->hasMany(ProductStock::class);
    }

    public function users(){
        return $this->belongsToMany(User::class, 'market_users');
    }

    
}
