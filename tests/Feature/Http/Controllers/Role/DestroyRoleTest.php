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

class DestroyRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.destroy';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $permission = Permission::create(['name' => CentralPermissions::DELETE_ROLE->value]);
        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);
    }

    public function test_authenticated_user_can_delete_role_without_users(): void
    {
        $role = Role::factory()->create(['name' => 'Deletable Role']);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success', __('Role :name deleted successfully.', ['name' => 'Deletable Role']));
        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_role(): void
    {
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

        $role = Role::factory()->create(['name' => 'Test Role']);

        $response = $this->delete(route($this->route, $role));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_delete_role(): void
    {
        $role = Role::factory()->create();
        auth()->guard('web')->logout();

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_cannot_delete_role_with_assigned_users(): void
    {
        $role = Role::factory()->create(['name' => 'Role With Users']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect();
        $response->assertSessionHas('error', __('Cannot delete role :name because it has users assigned to it.', ['name' => 'Role With Users']));
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Role With Users',
        ]);
    }

    public function test_delete_role_with_multiple_users_fails(): void
    {
        $role = Role::factory()->create(['name' => 'Multi User Role']);
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
        $this->assertEquals(3, $role->users()->count());
    }

    public function test_delete_role_preserves_other_roles(): void
    {
        $roleToDelete = Role::factory()->create(['name' => 'Role to Delete']);
        $roleToKeep = Role::factory()->create(['name' => 'Role to Keep']);

        $response = $this->delete(route($this->route, $roleToDelete));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', [
            'id' => $roleToDelete->id,
        ]);
        $this->assertDatabaseHas('roles', [
            'id' => $roleToKeep->id,
            'name' => 'Role to Keep',
        ]);
    }

    public function test_delete_role_after_removing_all_users_succeeds(): void
    {
        $role = Role::factory()->create(['name' => 'Role After User Removal']);
        $user = User::factory()->create();
        $user->assignRole($role);

        // First, remove the user from the role
        $user->removeRole($role);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_delete_nonexistent_role_returns_404(): void
    {
        $response = $this->delete(route($this->route, 999));

        $response->assertStatus(404);
    }

    public function test_delete_role_validates_user_count_accurately(): void
    {
        $role = Role::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Assign role to users
        $user1->assignRole($role);
        $user2->assignRole($role);

        // Remove one user
        $user1->removeRole($role);

        // Should still fail because one user remains
        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_delete_role_returns_to_previous_page_on_error(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $user->assignRole($role);

        // Set a previous URL
        $previousUrl = route('roles.show', $role);
        $this->from($previousUrl);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect($previousUrl);
        $response->assertSessionHas('error');
    }

    public function test_delete_role_success_message_includes_role_name(): void
    {
        $role = Role::factory()->create(['name' => 'Custom Role Name']);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success', __('Role :name deleted successfully.', ['name' => 'Custom Role Name']));
    }

    public function test_delete_role_error_message_includes_role_name(): void
    {
        $role = Role::factory()->create(['name' => 'Protected Role']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect();
        $response->assertSessionHas('error', __('Cannot delete role :name because it has users assigned to it.', ['name' => 'Protected Role']));
    }

    public function test_delete_role_handles_concurrent_deletion_gracefully(): void
    {
        $role = Role::factory()->create();

        // Simulate concurrent deletion by deleting the role directly
        $role->delete();

        $response = $this->delete(route($this->route, $role->id));

        $response->assertStatus(404);
    }

    public function test_delete_role_validates_permissions_are_not_deleted(): void
    {
        $role = Role::factory()->create();

        $this->seed(CentralPermissionsSeeder::class);
        $permission = CentralPermissions::CREATE_TENANT->value;

        $role->syncPermissions([$permission]);

        $response = $this->delete(route($this->route, $role));

        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('permissions', [
            'name' => $permission,
            'tenant_id' => null,
        ]);
    }
}
