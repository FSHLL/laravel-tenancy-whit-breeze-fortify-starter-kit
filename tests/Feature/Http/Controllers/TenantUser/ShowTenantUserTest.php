<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

use App\Enums\CentralPermissions;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShowTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $tenantUser;

    private string $route = 'tenants.users.show';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();
        $this->tenantUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::VIEW_TENANT_USER->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $this->authenticatedUser->assignRole($role);
    }

    public function test_authenticated_user_can_view_tenant_user_details(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.users.show');
        $response->assertViewHas('tenant', $this->tenant);
        $response->assertViewHas('user', $this->tenantUser);
    }

    public function test_user_without_permission_cannot_view_tenant_user(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(403);
    }

    public function test_show_displays_user_information(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($this->tenantUser->name);
        $response->assertSee($this->tenantUser->email);
        $response->assertSee($this->tenantUser->id);
    }

    public function test_show_displays_tenant_information(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($this->tenant->name);
    }

    public function test_show_displays_action_buttons(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(route('tenants.users.edit', [$this->tenant, $this->tenantUser]));
        $response->assertSee(__('Edit'));
        $response->assertSee(__('Back to List'));
    }

    public function test_show_displays_email_verification_status(): void
    {
        $verifiedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $verifiedUser]));

        $response->assertStatus(200);
        $response->assertSee(__('Verified'));
    }

    public function test_show_displays_unverified_email_status(): void
    {
        $unverifiedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $unverifiedUser]));

        $response->assertStatus(200);
        $response->assertSee(__('Not verified'));
    }

    public function test_show_displays_two_factor_authentication_status(): void
    {
        $userWith2FA = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'two_factor_secret' => 'some_secret',
        ]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $userWith2FA]));

        $response->assertStatus(200);
        $response->assertSee(__('Enabled'));
    }

    public function test_show_displays_disabled_two_factor_authentication(): void
    {
        $userWithout2FA = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'two_factor_secret' => null,
        ]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $userWithout2FA]));

        $response->assertStatus(200);
        $response->assertSee(__('Disabled'));
    }

    public function test_show_displays_delete_button(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(__('Delete user'));
    }

    public function test_unauthenticated_user_cannot_view_user_details(): void
    {
        $response = $this->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertRedirect(route('login'));
    }

    public function test_show_handles_non_existent_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [999, $this->tenantUser]));

        $response->assertStatus(404);
    }

    public function test_show_handles_non_existent_user(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, 999]));

        $response->assertStatus(404);
    }

    public function test_show_displays_user_timestamps(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($this->tenantUser->created_at->toDayDateTimeString());
        $response->assertSee($this->tenantUser->updated_at->toDayDateTimeString());
    }

    public function test_show_includes_delete_user_modal(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee("confirm-user-deletion-{$this->tenantUser->id}");
    }

    public function test_show_displays_link_to_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(route('tenants.show', $this->tenant));
    }

    public function test_show_displays_roles_section(): void
    {
        $role = Role::create(['name' => 'Admin', 'tenant_id' => $this->tenant->id]);
        $this->tenantUser->assignRole($role);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('Roles & Permissions');
        $response->assertSee($role->name);
    }

    public function test_show_displays_role_permissions(): void
    {
        $permission1 = Permission::create(['name' => 'view users', 'tenant_id' => $this->tenant->id]);
        $permission2 = Permission::create(['name' => 'edit users', 'tenant_id' => $this->tenant->id]);

        $role = Role::create(['name' => 'Admin', 'tenant_id' => $this->tenant->id]);
        $role->givePermissionTo([$permission1, $permission2]);

        $this->tenantUser->assignRole($role);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($role->name);
        $response->assertSee($permission1->name);
        $response->assertSee($permission2->name);
        $response->assertSee('2 permissions');
    }

    public function test_show_displays_no_roles_message(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('No roles assigned');
        $response->assertSee('This user does not have any roles assigned yet');
        $response->assertSee('Assign Roles');
    }

    public function test_show_displays_multiple_roles(): void
    {
        $role1 = Role::create(['name' => 'Admin', 'tenant_id' => $this->tenant->id]);
        $role2 = Role::create(['name' => 'Manager', 'tenant_id' => $this->tenant->id]);

        $this->tenantUser->assignRole([$role1, $role2]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($role1->name);
        $response->assertSee($role2->name);
    }
}
