<?php

namespace App\Models;

class Breakdown extends BaseModel
{
    protected $fillable = [
        'sale_id',
        'payment_id',
        'user_id'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
