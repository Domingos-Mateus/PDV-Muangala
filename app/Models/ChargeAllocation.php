<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeAllocation extends Model
{
    public $timestamps = false;

    protected $fillable = ['billing_receipt_id', 'charge_id', 'amount'];

    public function charge() { return $this->belongsTo(Charge::class); }
}
