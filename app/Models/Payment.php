<?php

namespace App\Models;

class Payment extends BaseModel
{
    const PAYMENT_STATUS_AVAILABLE = 'Disponible';
    const PAYMENT_STATUS_ASSIGNED = 'Conciliado';

    protected $table ='payments';
    protected $fillable = [
        'type_id',
        'method_id',
        'client_id',
        'username',
        'account',
        'amount',
        'amount_pending',
        'status'
    ];

    protected $hidden = [
        'type_id',
        'method_id',
        'client_id',
        'deleted_at',
    ];

    public function rules()
    {
        return [
            'type_id'           => 'required|numeric',
            'method_id'         => 'required|numeric',
            'client_id'         => 'required|numeric',
            'username'          => 'required',
            'account'           => 'required|numeric',
            'amount'            => 'required|numeric',
            'amount_pending'    => 'required|numeric'
        ];
    }

    public function isAvailable()
    {
        return $this->status == Payment::PAYMENT_STATUS_AVAILABLE;
    }

    public function method()
    {
        return $this->belongsTo(Method::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function breakdowns()
    {
        return $this->hasMany(Breakdown::class);
    }



}
