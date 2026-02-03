<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Driver;
use App\Models\Vehicle;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'profile_image',
        'birth_date',
        'national_number',
        'role',
        'otp',
        'otp_expires_at',
    ];


    protected $dates = [
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_expires_at',
    ];

    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class , 'owner_id');
    }


    public function drivers()
    {
        return $this->hasMany(Driver::class , 'owner_id'); 
    }
}
