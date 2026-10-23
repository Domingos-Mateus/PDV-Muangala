<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

class Role extends Model
{
    use LogsActivity;
    protected $fillable = ['store_id', 'name'];

    public function store()       { return $this->belongsTo(Store::class); }
    public function permissions() { return $this->belongsToMany(Permission::class); }
    public function users()       { return $this->hasMany(User::class); }
}
