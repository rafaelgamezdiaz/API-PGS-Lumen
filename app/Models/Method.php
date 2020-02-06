<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Method extends BaseModel
{
    use SoftDeletes;

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
