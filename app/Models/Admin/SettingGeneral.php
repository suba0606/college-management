<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingGeneral extends Model
{

    use HasFactory;

    protected $table = 'template_general_settings';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'display_name',
        'key',
        'value',
        'status',
        'trash',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected static function booted()
    {
        static::addGlobalScope(new TrashScope('template_general_settings'));
    }
}
