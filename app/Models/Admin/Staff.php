<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'admin_staff';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'user_id',
        'staff_id',
        'name',
        'gender',
        'dob',
        'mobile',
        'doj',
        'department',
        'qualification',
        'experience',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'status',
        'trash',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected static function booted()
    {
        static::addGlobalScope(new TrashScope('admin_staff'));
    }
   
}
