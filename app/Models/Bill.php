<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bills';
    protected $fillable = [
        'payment_id',
        'bill_id',
        'amount',
        'username',
        'account'
    ];

    protected $hidden = ['payment'];


    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function rules()
    {
        return [
            'payment_id'    => 'required',
            'bill_id'       => 'required',
            'amount'        => 'required',
            'username'      => 'required',
            'account'       => 'required|numeric'
        ];
    }
}