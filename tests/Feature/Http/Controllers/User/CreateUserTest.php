<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        tenancy()->initialize($this->tenant);

        $this->tenant->domains()->create(['domain' => $this->tenant->id]);

        $this->authenticatedUser = User::factory()->create();

        $this->route = "http://{$this->tenant->id}.localhost/users/create";
    }

    public function test_authenticated_user_can_access_create_user_page(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertViewIs('users.create');
    }

    public function test_unauthenticated_user_cannot_access_create_user_page(): void
    {
        $this->get($this->route)
            ->assertRedirect(route('login'));
    }

    public function test_create_user_page_contains_form_fields(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_create_user_page_has_proper_form_action(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('action="'.route('users.store').'"', false);
    }

    public function test_create_user_page_has_navigation_buttons(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Back to List')
            ->assertSee(route('users.index'))
            ->assertSee('Create User');
    }

    public function test_create_user_page_has_proper_title(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Create User');
    }

    public function test_create_user_page_shows_required_password_fields(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('required', false)
            ->assertDontSee('Leave blank to keep current password');
    }

    public function test_create_user_page_has_cancel_and_submit_buttons(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Cancel')
            ->assertSee('Create User');
    }

    public function test_create_user_page_displays_roles_section(): void
    {
        Role::factory()->create(['name' => 'Admin']);
        Role::factory()->create(['name' => 'Manager']);

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Assign Roles')
            ->assertSee('Admin')
            ->assertSee('Manager')
            ->assertSee('name="roles[]"', false);
    }

    public function test_create_user_page_shows_role_selection_controls(): void
    {
        Role::factory()->create(['name' => 'Admin']);

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Select All')
            ->assertSee('Select None')
            ->assertSee('id="select-all-roles"', false)
            ->assertSee('id="select-none-roles"', false);
    }

    public function test_create_user_page_handles_no_roles_available(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertDontSee('Assign Roles');
    }
}
