<?php

namespace App\Models\Admin;

use App\Scopes\TrashScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $table = 'template_user_role';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'role_name',
        'role_permission',
        'status',
        'trash',
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected static function booted()
    {
        static::addGlobalScope(new TrashScope('template_user_role'));
    }
    public function users()
    {
        return $this->hasMany(\App\Models\User::class,'role');
    }
}
