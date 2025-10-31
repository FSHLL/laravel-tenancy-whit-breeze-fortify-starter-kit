<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private string $route = 'tenants.users.create';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();
    }

    public function test_authenticated_user_can_view_create_user_form(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.users.create');
        $response->assertViewHas('tenant', $this->tenant);
    }

    public function test_create_form_displays_correct_tenant_information(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee($this->tenant->name);
        $response->assertSee($this->tenant->id);
    }

    public function test_create_form_has_required_fields(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    public function test_create_form_has_correct_action_url(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee(route('tenants.users.store', $this->tenant));
    }

    public function test_create_form_has_back_to_list_button(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee(route('tenants.users.index', $this->tenant));
        $response->assertSee(__('Back to List'));
    }

    public function test_create_form_displays_tenant_context_information(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee(__('Creating user for tenant'));
    }

    public function test_unauthenticated_user_cannot_access_create_form(): void
    {
        $response = $this->get(route($this->route, $this->tenant));

        $response->assertRedirect(route('login'));
    }

    public function test_create_form_handles_non_existent_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, 999));

        $response->assertStatus(404);
    }

    public function test_create_form_shows_correct_page_title(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee(__('Create User'));
    }

    public function test_create_form_has_csrf_protection(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
    }

    public function test_create_form_has_cancel_button(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, $this->tenant));

        $response->assertStatus(200);
        $response->assertSee(__('Cancel'));
    }
}
