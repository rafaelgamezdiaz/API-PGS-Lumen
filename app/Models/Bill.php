<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static where(string $string, $bill_id)
 */
class Bill extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bills';
    protected $fillable = [
        'payment_id',
        'bill_id',
        'amount',
        'username',
        'account',
        'amount_paid'
    ];

    //protected $hidden = ['payment'];


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
}
