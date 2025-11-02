<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EditUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $targetUser;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['id' => 'test-tenant']);

        tenancy()->initialize($this->tenant);

        $this->tenant->domains()->create(['domain' => $this->tenant->id]);

        $this->authenticatedUser = User::factory()->create();
        $this->targetUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->route = "http://{$this->tenant->id}.localhost/users/{$this->targetUser->id}/edit";
    }

    public function test_authenticated_user_can_access_edit_user_page(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas('user', $this->targetUser);
    }

    public function test_unauthenticated_user_cannot_access_edit_user_page(): void
    {
        $this->get($this->route)
            ->assertRedirect(route('login'));
    }

    public function test_edit_user_page_displays_current_user_data(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('value="'.$this->targetUser->name.'"', false)
            ->assertSee('value="'.$this->targetUser->email.'"', false);
    }

    public function test_edit_user_page_contains_form_fields(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_edit_user_page_has_proper_form_action(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('action="'.route('users.update', $this->targetUser).'"', false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_edit_user_page_shows_current_information_section(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Current Information')
            ->assertSee($this->targetUser->name)
            ->assertSee($this->targetUser->email)
            ->assertSee($this->targetUser->created_at->format('M d, Y'));
    }

    public function test_edit_user_page_shows_email_verification_status(): void
    {
        // Test verified user
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, $verifiedUser->id, $this->route))
            ->assertOk()
            ->assertSee('Email Verified')
            ->assertSee('Yes');

        // Test unverified user
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, $unverifiedUser->id, $this->route))
            ->assertOk()
            ->assertSee('Email Verified')
            ->assertSee('No');
    }

    public function test_edit_user_page_shows_optional_password_message(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Leave blank to keep current password');
    }

    public function test_edit_user_page_password_not_required(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->get($this->route);

        $content = $response->getContent();

        // Password field should not have required attribute in edit mode
        $this->assertStringNotContainsString('id="password" class="block mt-1 w-full" type="password" name="password" required', $content);
    }

    public function test_edit_user_page_has_proper_title(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Edit User');
    }

    public function test_edit_user_page_has_navigation_buttons(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Cancel')
            ->assertSee('Update User');
    }

    public function test_edit_user_page_has_proper_cancel_link(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('href="'.route('users.show', $this->targetUser).'"', false);
    }

    public function test_edit_nonexistent_user_returns_404(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, 99999, $this->route))
            ->assertNotFound();
    }

    public function test_edit_user_page_displays_creation_date(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Created')
            ->assertSee($this->targetUser->created_at->format('M d, Y'));
    }

    public function test_edit_user_form_has_csrf_protection(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_edit_user_form_has_method_override(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('name="_method"', false)
            ->assertSee('value="PUT"', false);
    }

    public function test_edit_user_page_displays_roles_section(): void
    {
        Role::factory()->create(['name' => 'Admin']);
        Role::factory()->create(['name' => 'Manager']);

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Assign Roles')
            ->assertSee('Admin')
            ->assertSee('Manager');
    }

    public function test_edit_user_page_shows_current_roles_checked(): void
    {
        Role::factory()->create(['name' => 'Admin']);
        $this->targetUser->assignRole('Admin');

        $response = $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk();

        $response->assertSee('value="Admin"', false);
        $response->assertSee('checked', false);
    }

    public function test_edit_user_page_shows_role_controls(): void
    {
        Role::factory()->create(['name' => 'User']);

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('Select All')
            ->assertSee('Select None');
    }
}
