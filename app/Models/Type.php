<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Type extends BaseModel
{
    use SoftDeletes;

    protected $table = 'types';
    protected $fillable = ['type'];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
