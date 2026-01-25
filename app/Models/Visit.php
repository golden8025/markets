<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        
        'sales_point_id',
        'visit_date',
        'previous_stock',
        'sold_qty',
        'minus_qty',
        'total_amount',
        'comment',

    ];
    
    public function images(){
        return $this->hasMany(VisitImage::class);
    }
}
