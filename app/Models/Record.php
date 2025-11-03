<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;


     protected $fillable = [
        'vehicle_id',
        'speed',
        'latitude',
        'longitude',
        'acceleration_x',
        'acceleration_y',
        'acceleration_z',
        'recorded_at',
    ];

    //  القراءة تتبع سيارة واحدة
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
