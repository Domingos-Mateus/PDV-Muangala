<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['sale_id', 'product_id', 'product_name', 'qty', 'unit_price', 'unit_cost', 'subtotal'];

    public function sale()    { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
