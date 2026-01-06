<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crash extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'trip_id',             
        'crash_at',          
        'latitude',            
        'longitude',           
        'location',            
        'severity',            
        'speed_before',        
        'acceleration_impact', 
    ];

    
    protected $casts = [
        'crash_at' => 'datetime',
    ];

   

    // الحادث مرتبط بسيارة واحدة
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // الحادثة دي حصلت ضمن رحلة
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}