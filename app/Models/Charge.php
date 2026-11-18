<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'subscription_id', 'description',
        'period_start', 'period_end', 'due_date', 'amount', 'paid_amount', 'status',
    ];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date'];

    public function customer()     { return $this->belongsTo(Customer::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }

    public function balance(): float
    {
        return round((float) $this->amount - (float) $this->paid_amount, 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->lt(today());
    }
}
