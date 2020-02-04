<?php

namespace App\Models;

class Method extends BaseModel
{
    protected $table = 'methods';
    protected $fillable = ['method'];

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
