<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'store_id', 'role_id', 'name', 'username', 'email',
        'employee_code', 'is_super_admin', 'is_active', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin'    => 'boolean',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function store() { return $this->belongsTo(Store::class); }
    public function role()  { return $this->belongsTo(Role::class); }

    public function hasPermission(string $key): bool
    {
        if (! $this->role_id) {
            return false;
        }

        $keys = Cache::remember("perms.{$this->role_id}", 300,
            fn () => $this->role->permissions()->pluck('key')->all()
        );

        return in_array($key, $keys);
    }
}
