<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'product_id', 'qty', 'min_stock'];

    public function product() { return $this->belongsTo(Product::class); }
}
