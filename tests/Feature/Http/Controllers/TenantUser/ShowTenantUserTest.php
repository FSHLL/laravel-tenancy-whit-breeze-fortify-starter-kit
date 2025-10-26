<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

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
    }

    /** @test */
    public function authenticated_user_can_view_tenant_user_details(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.users.show');
        $response->assertViewHas('tenant', $this->tenant);
        $response->assertViewHas('user', $this->tenantUser);
    }

    /** @test */
    public function show_displays_user_information(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($this->tenantUser->name);
        $response->assertSee($this->tenantUser->email);
        $response->assertSee($this->tenantUser->id);
    }

    /** @test */
    public function show_displays_tenant_information(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($this->tenant->name);
    }

    /** @test */
    public function show_displays_action_buttons(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(route('tenants.users.edit', [$this->tenant, $this->tenantUser]));
        $response->assertSee(__('Edit'));
        $response->assertSee(__('Back to List'));
    }

    /** @test */
    public function show_displays_email_verification_status(): void
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

    /** @test */
    public function show_displays_unverified_email_status(): void
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

    /** @test */
    public function show_displays_two_factor_authentication_status(): void
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

    /** @test */
    public function show_displays_disabled_two_factor_authentication(): void
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

    /** @test */
    public function show_displays_delete_button(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(__('Delete user'));
    }

    /** @test */
    public function unauthenticated_user_cannot_view_user_details(): void
    {
        $response = $this->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function show_handles_non_existent_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [999, $this->tenantUser]));

        $response->assertStatus(404);
    }

    /** @test */
    public function show_handles_non_existent_user(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, 999]));

        $response->assertStatus(404);
    }

    /** @test */
    public function show_displays_user_timestamps(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee($this->tenantUser->created_at->toDayDateTimeString());
        $response->assertSee($this->tenantUser->updated_at->toDayDateTimeString());
    }

    /** @test */
    public function show_includes_delete_user_modal(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee("confirm-user-deletion-{$this->tenantUser->id}");
    }

    /** @test */
    public function show_displays_link_to_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(route('tenants.show', $this->tenant));
    }
}
