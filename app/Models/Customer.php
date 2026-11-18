<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToStore, LogsActivity;

    protected $fillable = ['store_id', 'name', 'phone', 'nif', 'block', 'unit', 'notes', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function charges()       { return $this->hasMany(Charge::class); }
}
