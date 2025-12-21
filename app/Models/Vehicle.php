<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'make',
        'model',
        'plate_number',
        'year',
        'driver_id'
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

    // العربية عملت رحلات كتير
   public function trips()
   {
       return $this->hasMany(Trip::class);
   }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }


    
}
