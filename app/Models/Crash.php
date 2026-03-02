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
        'crashed_at',
        'latitude',
        'longitude',
        'location',
        'type',
        'severity', // النوع والخطورة
        'g_force_x',
        'g_force_y',
        'g_force_z',
        'yaw', // قراءات الحساسات
        'speed_before',
        'rpm_before'
    ];


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