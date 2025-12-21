<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id','driver_id',
        'start_time','end_time',
        'start_address','end_address',
        'start_lat','start_lng','end_lat','end_lng',
        'status','distance_km','avg_speed','max_speed'    
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    

    // 1. الرحلة تابعة لمركبة
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // 2. الرحلة تابعة لسائق
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // 3. الرحلة ليها مسار (نقاط تتبع كتير) 
    public function locations()
    {
        return $this->hasMany(Trip_location::class);
    }

    // 4. الرحلة ممكن يكون فيها حادثة  
    public function crash()
    {
        return $this->hasOne(Crash::class);
    }
}