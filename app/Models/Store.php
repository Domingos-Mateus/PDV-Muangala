<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogsActivity;

class Store extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'address', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::created(function (Store $store) {
            $templates = config('permissions.role_templates', []);

            foreach ($templates as $name => $keys) {
                $role = $store->roles()->create(['name' => $name]);

                $ids = $keys === ['*']
                    ? Permission::pluck('id')
                    : Permission::whereIn('key', $keys)->pluck('id');

                $role->permissions()->sync($ids);
            }
        });
    }

    public function roles() { return $this->hasMany(Role::class); }
    public function users() { return $this->hasMany(User::class); }
}
