<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToStore, LogsActivity;

    protected $fillable = ['store_id', 'name', 'default_price', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
