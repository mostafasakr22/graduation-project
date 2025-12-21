<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip_location extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id', 
        'latitude', 
        'longitude', 
        'speed', 
        'heading'
    ];

    // النقطة دي تابعة لرحلة معينة
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}