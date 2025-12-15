<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    
    protected $fillable = [
        'name',
        'national_number',
        'email',
        'password',
        'birth_date',
        'phone',
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
    ];

    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //  علاقة المستخدم مع السيارات
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
