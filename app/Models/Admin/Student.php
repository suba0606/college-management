<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'admin_student';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'gender',
        'dob',
        'rollno',
        'blood_group',
        'email',
        'mobile',
        'department',
        'joining_year',
        'section',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'photo',
        'current_year',
        'sslc_passing_year',
        'sslc_mark',
        'sslc_certificate',
        'hsc_passing_year',
        'hsc_mark',
        'hsc_certificate',
        'user_id',
        'placement_willing',
        'placement_status',
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
        static::addGlobalScope(new TrashScope('admin_student'));
    }
   
}
