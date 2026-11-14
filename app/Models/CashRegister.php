<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use BelongsToStore, LogsActivity;

    protected $fillable = ['store_id', 'name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function sessions() { return $this->hasMany(CashSession::class); }
}
