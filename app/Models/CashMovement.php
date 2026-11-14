<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'cash_session_id', 'user_id', 'type', 'amount', 'reason'];

    public function user() { return $this->belongsTo(User::class); }
}
