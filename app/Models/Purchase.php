<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'supplier_id', 'user_id', 'status', 'total', 'notes', 'received_at'];
    protected $casts = ['received_at' => 'datetime'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function items()    { return $this->hasMany(PurchaseItem::class); }
}
