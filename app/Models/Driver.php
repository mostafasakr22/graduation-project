<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'owner_id',
        'name',
        'email',
        'password',
        'national_number',
        'license_number',
        'phone',
    ];

    protected $hidden = [
        'password'
    ];

    
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    
     // السواق عمل رحلات كتير
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
