<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;
    protected $fillable = [
        
        'user_id',
        'market_id',
        'comment',
        

    ];
    
    public function images(){
        return $this->hasMany(VisitImage::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function info() {
        return $this->hasMany(VisitInfo::class);
    }

    public function market() {
        return $this->belongsTo(Market::class);
    }
}
