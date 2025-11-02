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

class IndexRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.index';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::VIEW_ROLE->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $this->user->assignRole($role);
    }

    public function test_authenticated_user_can_view_roles_index(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewIs($this->route);
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route($this->route));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_view_roles_index(): void
    {
        auth()->guard('web')->logout();

        $response = $this->get(route($this->route));

        $response->assertRedirect(route('login'));
    }

    public function test_roles_index_displays_permissions_count(): void
    {
        $role = Role::factory()->create();
        $this->seed(CentralPermissionsSeeder::class);
        $permissions = array_map(fn ($permission) => $permission->value, CentralPermissions::cases());
        $role->syncPermissions($permissions);

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('more');
    }

    public function test_roles_index_displays_empty_state_when_no_roles(): void
    {
        Role::truncate();
        $this->user->syncPermissions([CentralPermissions::VIEW_ROLE->value]);
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('No roles');
        $response->assertSee('Get started by creating a new role.');
        $response->assertSee('Create your first role');
    }

    public function test_roles_index_displays_action_buttons(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee(route('roles.show', $role));
        $response->assertSee(route('roles.edit', $role));
        $response->assertSee('confirm-role-deletion-'.$role->id);
    }

    public function test_roles_index_displays_create_role_button(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Create Role');
        $response->assertSee(route('roles.create'));
    }

    public function test_roles_index_counts_users_relationship(): void
    {
        $role = Role::factory()->create();
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $roles = $response->viewData('roles');
        $this->assertEquals(3, $roles->last()->users_count);
    }

    public function test_roles_index_displays_correct_page_title(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Role Management');
        $response->assertSeeInOrder(['Role Management', 'Create Role']);
    }

    public function test_roles_index_handles_large_number_of_roles(): void
    {
        Role::factory()->count(50)->create();

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $roles = $response->viewData('roles');
        $this->assertCount(15, $roles->items()); // Default pagination
        $this->assertEquals(51, $roles->total());
    }
}
