<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class BillingReceipt extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'cash_session_id', 'user_id', 'customer_id', 'seq', 'number', 'total', 'status',
        'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected $casts = ['cancelled_at' => 'datetime'];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function session()     { return $this->belongsTo(CashSession::class, 'cash_session_id'); }
    public function payments()    { return $this->hasMany(BillingReceiptPayment::class); }
    public function allocations() { return $this->hasMany(ChargeAllocation::class); }
        public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }
}
