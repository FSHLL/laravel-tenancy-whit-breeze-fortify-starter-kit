<?php

namespace Tests\Feature\Http\Controllers\Role;

use App\Enums\CentralPermissions;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CentralPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.update';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->seed(CentralPermissionsSeeder::class);
        $this->actingAs($user);
    }

    public function test_authenticated_user_can_update_role(): void
    {
        $role = Role::factory()->create(['name' => 'Old Role Name']);
        $permissions = collect(CentralPermissions::cases())->take(3);

        $updateData = [
            'name' => 'Updated Role Name',
            'permissions' => $permissions->pluck('value')->toArray(),
        ];

        $response = $this->put(route($this->route, $role), $updateData);

        $response->assertRedirect(route('roles.show', $role));
        $response->assertSessionHas('success', __('Role updated successfully.'));

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role Name',
        ]);

        $role->refresh();
        $this->assertEquals(3, $role->permissions()->count());
    }

    public function test_unauthenticated_user_cannot_update_role(): void
    {
        $role = Role::factory()->create();
        auth()->guard('web')->logout();

        $response = $this->put(route($this->route, $role), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_update_role_requires_name(): void
    {
        $role = Role::factory()->create(['name' => 'Original Name']);

        $response = $this->put(route($this->route, $role), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_update_role_requires_unique_name(): void
    {
        $existingRole = Role::factory()->create(['name' => 'Existing Role']);
        $roleToUpdate = Role::factory()->create(['name' => 'Role to Update']);

        $response = $this->put(route($this->route, $roleToUpdate), [
            'name' => 'Existing Role',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('roles', [
            'id' => $roleToUpdate->id,
            'name' => 'Role to Update',
        ]);
    }

    public function test_update_role_validates_name_length(): void
    {
        $role = Role::factory()->create();
        $longName = str_repeat('a', 256); // Assuming max length is 255

        $response = $this->put(route($this->route, $role), [
            'name' => $longName,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_role_replaces_all_permissions(): void
    {
        $role = Role::factory()->create();
        $oldPermissions = collect(CentralPermissions::cases())->take(3);

        $newPermissions = collect(CentralPermissions::cases())->skip(3)->take(2);

        $role->syncPermissions($oldPermissions);

        $updateData = [
            'name' => $role->name,
            'permissions' => $newPermissions->pluck('value')->toArray(),
        ];

        $response = $this->put(route($this->route, $role), $updateData);

        $response->assertRedirect(route('roles.show', $role));

        $role->refresh();
        $this->assertEquals(2, $role->permissions()->count());

        foreach ($newPermissions as $permission) {
            $this->assertTrue($role->hasPermissionTo($permission->value));
        }

        foreach ($oldPermissions as $permission) {
            $this->assertFalse($role->hasPermissionTo($permission->value));
        }
    }

    public function test_update_role_validates_permissions_exist(): void
    {
        $role = Role::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'permissions' => ['non-existent-permission'],
        ];

        $response = $this->put(route($this->route, $role), $updateData);

        $response->assertSessionHasErrors('permissions.*');
    }

    public function test_update_role_handles_duplicate_permissions_in_request(): void
    {
        $role = Role::factory()->create();
        $permission = CentralPermissions::CREATE_TENANT;

        $updateData = [
            'name' => 'Updated Name',
            'permissions' => [$permission->value, $permission->value], // Duplicate
        ];

        $response = $this->put(route($this->route, $role), $updateData);

        $response->assertRedirect(route('roles.show', $role));

        $role->refresh();
        $this->assertEquals(1, $role->permissions()->count());
    }

    public function test_update_role_affects_users_with_role(): void
    {
        $role = Role::factory()->create();
        $oldPermission = CentralPermissions::VIEW_TENANT;
        $newPermission = CentralPermissions::CREATE_TENANT;

        $role->syncPermissions([$oldPermission]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $updateData = [
            'name' => $role->name,
            'permissions' => [$newPermission->value],
        ];

        $response = $this->put(route($this->route, $role), $updateData);

        $response->assertRedirect(route('roles.show', $role));

        $user->refresh();
        $this->assertTrue($user->hasPermissionTo($newPermission->value));
        $this->assertFalse($user->hasPermissionTo($oldPermission->value));
    }

    public function test_update_nonexistent_role_returns_404(): void
    {
        $response = $this->put(route($this->route, 999), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(404);
    }

    public function test_update_role_with_multiple_validation_errors(): void
    {
        Role::factory()->create(['name' => 'Existing Role']);
        $roleToUpdate = Role::factory()->create();

        $updateData = [
            'name' => '', // Required validation error
            'permissions' => ['non-existent-permission'], // Permissions validation error
        ];

        $response = $this->put(route($this->route, $roleToUpdate), $updateData);

        $response->assertSessionHasErrors();
    }
}
