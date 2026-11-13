<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToStore, LogsActivity;

    protected $fillable = [
        'store_id', 'category_id', 'name', 'sku', 'barcode',
        'unit', 'cost_price', 'sale_price', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class); }
    public function stock()    { return $this->hasOne(ProductStock::class); }
}
