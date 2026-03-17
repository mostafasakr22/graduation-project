<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip_location extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        
        // GPS Data
        'latitude',
        'longitude',
        'speed',
        'heading',
        'altitude',
        'sats', // عدد الأقمار (جودة الإشارة)
        
        // OBD Data
        'rpm',
        'coolant_temp', // حرارة المحرك
        'fuel_level',   // مستوى الوقود
        'dtc_codes',    // أكواد الأعطال (إن وجدت)

        // IMU Sensors
        'ax', // تسارع أمامي/خلفي
        'ay', // تسارع جانبي
        'az', // تسارع رأسي
        'yaw', // الانحراف
        'pitch', // الميلان الأمامي
        'roll', // الميلان الجانبي
    ];

    // النقطة دي تابعة لرحلة معينة
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}