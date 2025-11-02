<?php

namespace Tests\Feature\Http\Controllers\Role;

use App\Enums\CentralPermissions;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CentralPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.create';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::CREATE_ROLE->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
    }

    public function test_authenticated_user_can_access_create_role_page(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewIs('roles.create');
    }

    public function test_user_without_permission_cannot_access_create(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route($this->route));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_create_role_page(): void
    {
        auth()->guard('web')->logout();

        $response = $this->get(route($this->route));

        $response->assertRedirect(route('login'));
    }

    public function test_create_role_page_displays_permissions(): void
    {
        $this->seed(CentralPermissionsSeeder::class);
        $permissions = CentralPermissions::cases();

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewHas('permissions');
        $response->assertSee('Select All');
        $response->assertSee('Select None');
        $response->assertSee('id="select-all"', false);
        $response->assertSee('id="select-none"', false);
        $response->assertSee('Cancel');
        $response->assertSee('Create Role');
        $response->assertSee('type="submit"', false);
        $response->assertSee('action="'.route('roles.store').'"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('Create Role');
        $response->assertSeeInOrder(['Create Role', 'Back to Roles']);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="permissions[]"', false);

        foreach ($permissions as $permission) {
            $response->assertSee($permission->value);
            $response->assertSee('value="'.$permission->value.'"', false);
        }
    }
}
