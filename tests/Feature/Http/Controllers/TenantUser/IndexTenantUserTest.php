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

class IndexTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private string $route = 'tenants.users.index';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::VIEW_TENANT_USER->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $this->authenticatedUser->assignRole($role);
    }

    public function test_authenticated_user_can_view_tenant_users_index(): void
    {
        $tenantUsers = User::factory(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.users.index');
        $response->assertViewHas('tenant', $this->tenant);
        $response->assertViewHas('users');

        foreach ($tenantUsers as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(403);
    }

    public function test_index_only_shows_users_from_specific_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $tenantUsers = User::factory(2)->create(['tenant_id' => $this->tenant->id]);
        $otherTenantUsers = User::factory(2)->create(['tenant_id' => $otherTenant->id]);
        $centralUsers = User::factory(2)->create(['tenant_id' => null]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);

        foreach ($tenantUsers as $user) {
            $response->assertSee($user->name);
        }

        foreach ($otherTenantUsers as $user) {
            $response->assertDontSee($user->name);
        }

        foreach ($centralUsers as $user) {
            $response->assertDontSee($user->name);
        }
    }

    public function test_index_view_has_pagination(): void
    {
        User::factory(20)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $users = $response->viewData('users');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $users);
    }

    public function test_unauthenticated_user_cannot_access_tenant_users_index(): void
    {
        $response = $this->get(route($this->route, $this->tenant));

        $response->assertRedirect(route('login'));
    }

    public function test_index_handles_non_existent_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, 999));

        $response->assertStatus(404);
    }
}
