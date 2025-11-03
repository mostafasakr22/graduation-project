<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crash extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'crash_time',
        'latitude',
        'longitude',
        'severity',
        'speed_before',
        'acceleration_impact',
    ];

    // الحادث مرتبط بسيارة واحدة
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
