<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EditTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $tenantUser;

    private string $route = 'tenants.users.edit';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();
        $this->tenantUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_authenticated_user_can_view_edit_form(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.users.edit');
        $response->assertViewHas('tenant', $this->tenant);
        $response->assertViewHas('user', $this->tenantUser);
    }

    public function test_edit_form_displays_with_existing_user_data(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('value="'.$this->tenantUser->name.'"', false);
        $response->assertSee('value="'.$this->tenantUser->email.'"', false);
    }

    public function test_edit_form_displays_required_form_fields(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    public function test_edit_form_displays_cancel_button(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(__('Cancel'));
        $response->assertSee(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
    }

    public function test_edit_form_displays_update_button(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee(__('Update User'));
        $response->assertSee('type="submit"', false);
    }

    public function test_edit_form_includes_method_spoofing(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('name="_method"', false);
        $response->assertSee('value="PUT"', false);
    }

    public function test_unauthenticated_user_cannot_view_edit_form(): void
    {
        $response = $this->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_handles_non_existent_tenant(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [999, $this->tenantUser]));

        $response->assertStatus(404);
    }

    public function test_edit_handles_non_existent_user(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, 999]));

        $response->assertStatus(404);
    }

    public function test_edit_form_includes_old_input_values(): void
    {
        $oldName = $this->faker->name;
        $oldEmail = $this->faker->email;
        session()->flashInput(['name' => $oldName, 'email' => $oldEmail]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('value="'.$oldName.'"', false);
        $response->assertSee('value="'.$oldEmail.'"', false);
    }

    public function test_edit_form_passes_edit_mode_to_partial(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertViewHasAll([
            'tenant' => $this->tenant,
            'user' => $this->tenantUser,
        ]);
    }

    public function test_edit_form_displays_roles_section(): void
    {
        $role1 = Role::create(['name' => 'Admin', 'tenant_id' => $this->tenant->id]);
        $role2 = Role::create(['name' => 'Manager', 'tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('Assign Roles');
        $response->assertSee($role1->name);
        $response->assertSee($role2->name);
    }

    public function test_edit_form_shows_current_roles_checked(): void
    {
        $role = Role::create(['name' => 'Admin', 'tenant_id' => $this->tenant->id]);
        $this->tenantUser->assignRole($role);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('value="Admin"', false);
        $response->assertSee('checked', false);
    }

    public function test_edit_form_shows_role_controls(): void
    {
        Role::create(['name' => 'User', 'tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get(route($this->route, [$this->tenant, $this->tenantUser]));

        $response->assertStatus(200);
        $response->assertSee('Select All');
        $response->assertSee('Select None');
    }
}
