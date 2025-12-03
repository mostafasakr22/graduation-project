<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'national_number',
        'license_number',
        'email',
        'password',
        'phone',
        'user_id'
    ];

    protected $hidden = [
        'password'
    ];

    
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }
}
