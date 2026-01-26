<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;
    protected $fillable = [
        
        'market_id',
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
