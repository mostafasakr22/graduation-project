<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crash extends Model
{
    use HasFactory;

    protected $fillable = [
        // الأساسيات
        'vehicle_id',
        'trip_id',
        'crashed_at',
        'latitude',
        'longitude',
        'location',
        
        // التصنيف
        'type',      // major_crash, hard_braking...
        'severity',  // low, medium, critical

        // قراءات الحساسات وقت الحادث (IMU)
        'ax',        // بدل g_force_x
        'ay',        // بدل g_force_y
        'az',        // بدل g_force_z
        'yaw',
        'pitch',     // الانخفاض/الارتفاع
        'roll',      // الانقلاب الجانبي
        
        // حالة السيارة والمحرك (OBD & GPS)
        'speed_before',
        'rpm_before',
        'coolant_temp',
        'fuel_level',
        'dtc_codes',
        'sats'
    ];

    // تحويل الوقت
    protected $casts = [
        'crashed_at' => 'datetime',
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