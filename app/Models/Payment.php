<?php

namespace App\Models;

use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static create(array $array)
 * @method static where(string $string, $account)
 */
class Payment extends BaseModel
{
    use SoftDeletes, ApiResponser;

    const PAYMENT_STATUS_AVAILABLE = 'Disponible';
    const PAYMENT_STATUS_ASSIGNED = 'Conciliado';
    const PAYMENT_STATUS_NULL = 'Anulado';

    protected $table ='payments';
    protected $fillable = [
        'reference',
        'type_id',
        'method_id',
        'client_id',
        'username',
        'account',
        'amount',
        'amount_pending',
        'status',
        'payment_date',
        'description_deleted'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function rules()
    {
        return [
            'reference'         => 'required',
            'type_id'           => 'required|numeric',
            'method_id'         => 'required|numeric',
            'client_id'         => 'required|numeric',
            'username'          => 'required',
            'account'           => 'required|numeric',
            'amount'            => 'required|numeric',
        ];
    }

    public function rulesPaymentConciliated()
    {
        return [
            'type_id'           => 'required|numeric',
            'method_id'         => 'required|numeric',
            'client_id'         => 'required|numeric',
            'username'          => 'required',
            'account'           => 'required|numeric',
            'amount'            => 'required|numeric',
            'bill_id'           => 'numeric'
        ];
    }

    public function rules_update()
    {
        return [
            'amount_pending'    => 'numeric',
            'type_id'           => 'numeric',
            'method_id'         => 'numeric',
            'client_id'         => 'numeric',
            'account'           => 'numeric',
            'amount'            => 'numeric'
        ];
    }

    public function isAvailable()
    {
        return $this->status == Payment::PAYMENT_STATUS_AVAILABLE;
    }

    public function changeStatus($request){
        if ( $this->status == Payment::PAYMENT_STATUS_AVAILABLE ) {
            $this->status = Payment::PAYMENT_STATUS_NULL;
            $this->description_deleted = $request->description_deleted;
        }else{
            $this->status = Payment::PAYMENT_STATUS_AVAILABLE;
            $this->description_deleted = null;
        }
        if($this->save()){
            return $this->successResponse("Pago ".$this->status);
        }
        return $this->errorMessage('Ha ocurrido un error al intentar cambiar el status del pago.');
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


    /**
     * @param $value
     * @return string
     */
    public function getCreatedAtAttribute($value)
    {
        return $this->parseDate($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function getUpdatedAtAttribute($value)
    {
        return $this->parseDate($value);
    }

    /**
     * @param $value
     * @return string
     */
    public function getPaymentDateAttribute($value)
    {
        return $this->parseDate($value);
    }


}
