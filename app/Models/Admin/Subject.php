<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'admin_subject';
    protected $primarykey = "id";


    protected $fillable = [
        'id',
        'subject_name',
        'department',
        'semester',
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
        static::addGlobalScope(new TrashScope('admin_subject'));
    }
}
