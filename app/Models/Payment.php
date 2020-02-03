<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends BaseModel
{
    use SoftDeletes;

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
            'amount'            => 'required|numeric'
        ];
    }

    public function rules_update()
    {
        return [
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

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }



}
