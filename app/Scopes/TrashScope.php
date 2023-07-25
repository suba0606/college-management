<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TrashScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */

     protected $tableName;

     public function __construct($tableName = '') {
        $this->tableName = $tableName;
   }


    public function apply(Builder $builder, Model $model)
    {

        if( $this->tableName != ''){
            $builder->where($this->tableName.'.trash', '=', 'NO');
        }else{
            $builder->where('trash', '=', 'NO');
        }
        
    }
}
