<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    use HasFactory;

    protected $table = 'admin_holiday_events';
    protected $primarykey = "id";


    protected $fillable = [
        'id',
        'event_type',
        'event_date',
        'event_name',
        'department',
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
        static::addGlobalScope(new TrashScope('admin_holiday_events'));
    }
}
