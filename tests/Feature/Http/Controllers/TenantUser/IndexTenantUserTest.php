<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

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
    }

    /** @test */
    public function authenticated_user_can_view_tenant_users_index(): void
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

    /** @test */
    public function index_only_shows_users_from_specific_tenant(): void
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

    /** @test */
    public function index_view_has_pagination(): void
    {
        User::factory(20)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $users = $response->viewData('users');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $users);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_tenant_users_index(): void
    {
        $response = $this->get(route($this->route, $this->tenant));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function index_handles_non_existent_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, 999));

        $response->assertStatus(404);
    }
}
