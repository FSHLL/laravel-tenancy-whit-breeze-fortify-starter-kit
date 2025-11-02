<?php

namespace Tests\Feature\Http\Controllers\Role;

use App\Enums\CentralPermissions;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CentralPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StoreRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.store';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->seed(CentralPermissionsSeeder::class);

        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo([CentralPermissions::CREATE_ROLE->value]);
        $user->assignRole($role);

        $this->actingAs($user);
    }

    public function test_authenticated_user_can_create_role(): void
    {
        $permissions = collect(CentralPermissions::cases());

        $roleData = [
            'name' => 'Test Role',
            'permissions' => $permissions->pluck('value')->toArray(),
        ];

        $response = $this->post(route($this->route), $roleData);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success', __('Role created successfully.'));

        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
        ]);

        $role = Role::where('name', 'Test Role')->first();
        $this->assertEquals($permissions->count(), $role->permissions()->count());
    }

    public function test_user_without_permission_cannot_store_role(): void
    {
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

        $response = $this->post(route($this->route), [
            'name' => 'Test Role',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_create_role(): void
    {
        auth()->guard('web')->logout();

        $response = $this->post(route($this->route), [
            'name' => 'Test Role',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_store_role_requires_name(): void
    {
        $response = $this->post(route($this->route), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('roles', [
            'name' => '',
        ]);
    }

    public function test_store_role_requires_unique_name(): void
    {
        Role::factory()->create(['name' => 'Existing Role']);

        $response = $this->post(route($this->route), [
            'name' => 'Existing Role',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, Role::where('name', 'Existing Role')->count());
    }

    public function test_store_role_validates_name_length(): void
    {
        $longName = str_repeat('a', 256); // Assuming max length is 255

        $response = $this->post(route($this->route), [
            'name' => $longName,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('roles', [
            'name' => $longName,
        ]);
    }

    public function test_store_role_validates_permissions_exist(): void
    {
        $roleData = [
            'name' => 'Test Role',
            'permissions' => ['non-existent-permission'],
        ];

        $response = $this->post(route($this->route), $roleData);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('roles', [
            'name' => 'Test Role',
        ]);
    }

    public function test_store_role_syncs_multiple_permissions(): void
    {
        $permissions = collect(CentralPermissions::cases());

        $roleData = [
            'name' => 'Multi Permission Role',
            'permissions' => $permissions->pluck('value')->toArray(),
        ];

        $response = $this->post(route($this->route), $roleData);

        $response->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'Multi Permission Role')->first();
        $this->assertEquals($permissions->count(), $role->permissions()->count());

        foreach ($permissions as $permission) {
            $this->assertTrue($role->hasPermissionTo($permission->value));
        }
    }

    public function test_store_role_handles_duplicate_permissions_in_request(): void
    {
        $permissions = collect(CentralPermissions::cases());

        $roleData = [
            'name' => 'Duplicate Test Role',
            'permissions' => [$permissions->first()->value, $permissions->first()->value], // Duplicate
        ];

        $response = $this->post(route($this->route), $roleData);

        $response->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'Duplicate Test Role')->first();
        $this->assertEquals(1, $role->permissions()->count());
    }
}
