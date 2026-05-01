<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'start_time',
        'end_time',
        'start_address',
        'end_address',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'status',
        'distance_km',
        'avg_speed',
        'max_speed'
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

    // دالة لحساب المسافة الكلية للرحلة بناءً على النقاط المسجلة
    public function calculateDistance()
    {
        $locations = $this->locations()->orderBy('created_at', 'asc')->get();
        $totalDistance = 0;

        for ($i = 0; $i < count($locations) - 1; $i++) {
            $totalDistance += $this->haversineDistance(
                $locations[$i]->latitude,
                $locations[$i]->longitude,
                $locations[$i + 1]->latitude,
                $locations[$i + 1]->longitude
            );
        }
        return $totalDistance;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}