<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'customer_id', 'service_id', 'price', 'frequency', 'interval_count',
        'start_date', 'next_charge_date', 'end_date', 'status',
    ];

    protected $casts = ['start_date' => 'date', 'next_charge_date' => 'date', 'end_date' => 'date'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function service()  { return $this->belongsTo(Service::class); }
    public function charges()  { return $this->hasMany(Charge::class); }
}
