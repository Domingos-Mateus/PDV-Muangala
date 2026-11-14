<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'cash_session_id', 'user_id', 'seq', 'number',
        'subtotal', 'discount', 'total', 'status',
        'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected $casts = ['cancelled_at' => 'datetime'];

    public function user()     { return $this->belongsTo(User::class); }
    public function session()  { return $this->belongsTo(CashSession::class, 'cash_session_id'); }
    public function items()    { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
