<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
         Event::listen(Login::class, fn ($e) => ActivityLog::record(
        $e->user, 'login', 'Entrou no sistema', [], $e->user->store_id, $e->user->id));

    Event::listen(Logout::class, function ($e) {
        if ($e->user) {
            ActivityLog::record($e->user, 'logout', 'Saiu do sistema', [], $e->user->store_id, $e->user->id);
        }
    });

    Event::listen(Failed::class, fn ($e) => ActivityLog::record(
        null, 'login_failed', 'Tentativa de login falhada',
        ['username' => $e->credentials['username'] ?? null], $e->user?->store_id, $e->user?->id));
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
