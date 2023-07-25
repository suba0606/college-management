<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'admin_attendance_details';
    protected $primarykey = "id";


    protected $fillable = [
        'id',
        'attendance_id',
        'department',
        'current_year',
        'section',
        'student_id',
        'date',
        'attendance_status',
        'submitted_user',
        'status',
        'trash',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',

    ];

    protected $attributes = [
        'status' => 1,
        'trash' => 'NO',
    ];



    protected static function booted()
    {
        static::addGlobalScope(new TrashScope('admin_attendance_details'));
    }
}
