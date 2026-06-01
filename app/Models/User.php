<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $guarded = [];
    protected $hidden = ['password'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function tenant() {
        return $this->hasOne(Tenant::class);
    }

    public function activityLogs() {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications() {
        return $this->hasMany(Notification::class);
    }
}