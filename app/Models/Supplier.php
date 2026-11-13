<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToStore, LogsActivity;

    protected $fillable = ['store_id', 'name', 'nif', 'phone', 'email', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function purchases() { return $this->hasMany(Purchase::class); }
}
