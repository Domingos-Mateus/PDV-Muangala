<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    use BelongsToStore, LogsActivity;

    protected $fillable = [
        'store_id', 'cash_register_id', 'user_id', 'status', 'opening_amount',
        'expected_amount', 'counted_amount', 'difference', 'notes', 'opened_at', 'closed_at',
    ];

    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function register()  { return $this->belongsTo(CashRegister::class, 'cash_register_id'); }
    public function user()      { return $this->belongsTo(User::class); }
    public function movements() { return $this->hasMany(CashMovement::class); }

    public function logTitle(): string
    {
        return 'Turno de caixa #'.$this->getKey();
    }
}
