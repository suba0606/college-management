<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeftMenu extends Model
{
    use HasFactory;

    protected $table = 'template_left_menu';
    protected $primarykey = "id";


    protected $fillable = [
        'id',
        'name',
        'link',
        'icon',
        'parent_id',
        'is_parent',
        'is_module',
        'sort_order',
        'status',
        'trash',
        'created_at',
        'updated_at',

    ];

    protected $attributes = [
        'status' => 1,
        'trash' => 'NO',
    ];



    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'link' => 'string',
        'icon' => 'string',
        'parent_id' => 'integer',
        'is_module' => 'integer',
        'sort_order' => 'integer',
        'created_by' => 'integer',
        'status' => 'integer',
        'trash' => 'string',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TrashScope('template_left_menu'));
    }
}
