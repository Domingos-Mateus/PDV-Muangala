<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingReceiptPayment extends Model
{
    protected $fillable = ['billing_receipt_id', 'method', 'amount', 'tendered', 'change_given'];
}
