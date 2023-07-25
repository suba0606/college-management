<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Scopes\TrashScope;

class User extends Authenticatable {

    use HasFactory,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone',
        'ext',
        'mobile',
        'profile_image',
        'is_active',
        'active_tokan',
        'created_by',
        'created_date',
        'password_date',
        'website_access',
        'status',
        'trash',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function booted() {
        static::addGlobalScope(new TrashScope);
    }

    public function roleDetails()
    {
        return $this->belongsTo(\App\Models\Admin\UserRole::class, 'role');
    }

}
