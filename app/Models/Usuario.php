<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_huesped';

    protected $fillable = [
        'name',
        'email',
        'passwordd',
        'role',
    ];

    protected $hidden = [
        'passwordd',
        'remember_token',
    ];

    
    public function getPasswordAttribute()
    {
        return $this->passwordd;
    }


    public function setPasswordAttribute($value)
    {
        $this->attributes['passwordd'] = $value;
    }

    public function getAuthPassword()
    {
        return $this->passwordd;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

   
}