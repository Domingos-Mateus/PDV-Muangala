<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // O super admin pode tudo
        Gate::before(fn ($user) => $user->is_super_admin ? true : null);

        // Uma Gate por permissão (products.view, sales.create...)
        if (Schema::hasTable('permissions')) {
            foreach (Permission::pluck('key') as $key) {
                Gate::define($key, fn ($user) => $user->is_active && $user->hasPermission($key));
                Gate::define('super-admin', fn ($user) => (bool) $user->is_super_admin);
            }
        }
    }
}
