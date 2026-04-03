<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitInfo extends Model
{
    protected $fillable = [
        'product_id',
        'visit_id',
        'loaded',
        'left',
        'profit',
        'sold'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function visit(){
        return $this->belongsTo(Visit::class);
    }
}
