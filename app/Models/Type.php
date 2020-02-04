<?php

namespace App\Models;

class Type extends BaseModel
{
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
