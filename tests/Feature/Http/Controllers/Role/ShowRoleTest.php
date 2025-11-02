<?php

namespace Tests\Feature\Http\Controllers\Role;

use App\Enums\CentralPermissions;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShowRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.show';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $permission = Permission::create(['name' => CentralPermissions::VIEW_ROLE->value]);
        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->actingAs($user);
    }

    public function test_authenticated_user_can_view_role_details(): void
    {
        $role = Role::factory()->create(['name' => 'Test Role']);

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertViewIs($this->route);
        $response->assertViewHas('role');
    }

    public function test_user_without_permission_cannot_view_role_details(): void
    {
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

        $role = Role::factory()->create(['name' => 'Test Role']);

        $response = $this->get(route($this->route, $role));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_role_details(): void
    {
        $role = Role::factory()->create();
        auth()->guard('web')->logout();

        $response = $this->get(route($this->route, $role));

        $response->assertRedirect(route('login'));
    }

    public function test_show_role_displays_basic_information(): void
    {
        $role = Role::factory()->create([
            'name' => 'Administrator Role',
            'guard_name' => 'web',
        ]);

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('Administrator Role');
        $response->assertSee('web');
        $response->assertSee($role->created_at->toDayDateTimeString());
        $response->assertSee($role->updated_at->toDayDateTimeString());
    }

    public function test_show_role_displays_users_with_role(): void
    {
        $role = Role::factory()->create();
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
        $response->assertSee('2 users');
    }

    public function test_show_role_displays_empty_permissions_state(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('0 permissions');
    }

    public function test_show_role_displays_empty_users_state(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('No users assigned');
        $response->assertSee('No users have been assigned to this role yet.');
    }

    public function test_show_role_displays_action_buttons(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('Back to Roles');
        $response->assertSee('Edit Role');
        $response->assertSee('Delete Role');
        $response->assertSee(route('roles.index'));
        $response->assertSee(route('roles.edit', $role));
    }

    public function test_show_role_displays_delete_confirmation_modal(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('confirm-role-deletion-'.$role->id);
    }

    public function test_show_role_with_nonexistent_role_returns_404(): void
    {
        $response = $this->get(route($this->route, 999));

        $response->assertStatus(404);
    }

    public function test_show_role_displays_security_icons(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('svg');
        $response->assertSee('viewBox');
    }

    public function test_show_role_displays_users_count(): void
    {
        $role = Role::factory()->create();
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('5 users');
    }

    public function test_show_role_displays_user_email_verification_status(): void
    {
        $role = Role::factory()->create();
        $verifiedUser = User::factory()->create(['name' => 'Verified User', 'email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create(['name' => 'Unverified User']);

        $verifiedUser->assignRole($role);
        $unverifiedUser->assignRole($role);

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('Verified User');
        $response->assertSee('Unverified User');
    }

    public function test_show_role_passes_role_to_view(): void
    {
        $role = Role::factory()->create(['name' => 'View Test Role']);

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $viewRole = $response->viewData('role');
        $this->assertEquals('View Test Role', $viewRole->name);
        $this->assertEquals($role->id, $viewRole->id);
    }

    public function test_show_role_displays_correct_page_structure(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('Role Details');
        $response->assertSee('Basic Information');
        $response->assertSee('Permissions');
        $response->assertSee('Users with this role');
    }
}
