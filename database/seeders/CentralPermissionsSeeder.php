<?php

namespace Database\Seeders;

use App\Enums\CentralPermissions;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class CentralPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (CentralPermissions::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }
    }
}
