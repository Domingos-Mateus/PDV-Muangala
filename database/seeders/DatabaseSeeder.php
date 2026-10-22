<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Administrador', 'password' => 'mude-esta-senha', 'is_super_admin' => true]
        );
    }
}
