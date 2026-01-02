<?php

namespace Database\Seeders;

use App\Enums\Permissions;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::all()->runForEach(function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            foreach (Permissions::cases() as $permission) {
                Permission::firstOrCreate(['name' => $permission->value]);
            }
        });
    }
}
