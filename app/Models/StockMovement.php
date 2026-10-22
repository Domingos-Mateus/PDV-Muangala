<?php

namespace App\Models;

use App\Enums\MovementType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'product_id', 'user_id', 'type', 'qty',
        'qty_before', 'qty_after', 'unit_cost',
        'reference_type', 'reference_id', 'note',
    ];

    protected $casts = ['type' => MovementType::class];

    public function product()   { return $this->belongsTo(Product::class); }
    public function user()      { return $this->belongsTo(User::class); }
    public function reference() { return $this->morphTo(); }
}
