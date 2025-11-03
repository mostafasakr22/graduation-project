<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plate_number',
        'model',
        'year',
    ];

    //  السيارة تتبع مستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //  السيارة تحتوي على قراءات متعددة
    public function records()
    {
        return $this->hasMany(Record::class);
    }

    // السيارة تحتوي على عدة حوادث
    public function crashes()
    {
        return $this->hasMany(Crash::class);
    }
}
