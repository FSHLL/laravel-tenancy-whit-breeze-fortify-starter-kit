<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $tenantUser;

    private string $route = 'tenants.users.update';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();
        $this->tenantUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_authenticated_user_can_update_tenant_user(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->assertDatabaseHas('users', [
            'id' => $this->tenantUser->id,
            'name' => $updateData['name'],
            'email' => $updateData['email'],
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_update_with_password_changes_user_password(): void
    {
        $newPassword = 'NewSecurePassword123!';
        $updateData = [
            'name' => $this->tenantUser->name,
            'email' => $this->tenantUser->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->tenantUser->password));
    }

    public function test_update_without_password_keeps_existing_password(): void
    {
        $originalPassword = $this->tenantUser->password;
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertEquals($originalPassword, $this->tenantUser->password);
    }

    public function test_update_fails_with_invalid_name(): void
    {
        $updateData = [
            'name' => '', // Invalid: empty name
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();
    }

    public function test_update_fails_with_invalid_email(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => 'invalid-email', // Invalid: not a valid email
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    public function test_update_fails_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $updateData = [
            'name' => $this->faker->name,
            'email' => $existingUser->email, // Invalid: email already exists
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    public function test_update_allows_keeping_same_email(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->tenantUser->email, // Same email should be allowed
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->assertDatabaseHas('users', [
            'id' => $this->tenantUser->id,
            'name' => $updateData['name'],
            'email' => $updateData['email'],
        ]);
    }

    public function test_update_fails_with_short_password(): void
    {
        $shortPassword = '123'; // Too short
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $shortPassword,
            'password_confirmation' => $shortPassword,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();
    }

    public function test_update_fails_with_mismatched_password_confirmation(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!', // Mismatched
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();
    }

    public function test_update_resets_email_verification_when_email_changes(): void
    {
        $this->tenantUser->update(['email_verified_at' => now()]);
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail, // Different email
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertNull($this->tenantUser->email_verified_at);
    }

    public function test_update_preserves_email_verification_when_email_unchanged(): void
    {
        $verificationTime = now();
        $this->tenantUser->update(['email_verified_at' => $verificationTime]);
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->tenantUser->email, // Same email
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertEquals($verificationTime->timestamp, $this->tenantUser->email_verified_at->timestamp);
    }

    public function test_update_displays_success_message(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHas('success');
        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
    }

    public function test_unauthenticated_user_cannot_update_user(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('login'));
    }

    public function test_update_handles_non_existent_tenant(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        // Act & Assert
        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [999, $this->tenantUser]), $updateData);

        $response->assertStatus(404);
    }

    public function test_update_handles_non_existent_user(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];

        // Act & Assert
        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, 999]), $updateData);

        $response->assertStatus(404);
    }

    public function test_update_validates_name_length(): void
    {
        $updateData = [
            'name' => str_repeat('a', 256), // Too long
            'email' => $this->faker->unique()->safeEmail,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();
    }

    public function test_update_validates_email_length(): void
    {
        $longEmail = str_repeat('a', 250).'@example.com'; // Too long
        $updateData = [
            'name' => $this->faker->name,
            'email' => $longEmail,
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
    }

    public function test_update_preserves_tenant_id(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'tenant_id' => 999, // Attempt to change tenant_id
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertEquals($this->tenant->id, $this->tenantUser->tenant_id);
    }

    public function test_update_handles_mass_assignment_protection(): void
    {
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'id' => 999, // Attempt to change ID
            'created_at' => now()->subYear(), // Attempt to change timestamp
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertNotEquals(999, $this->tenantUser->id);
        $this->assertNotEquals($updateData['created_at']->timestamp, $this->tenantUser->created_at->timestamp);
    }

    public function test_update_with_empty_password_fields_is_ignored(): void
    {
        $originalPassword = $this->tenantUser->password;
        $updateData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => '',
            'password_confirmation' => '',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $this->tenantUser]));
        $this->tenantUser->refresh();
        $this->assertEquals($originalPassword, $this->tenantUser->password);
    }

    public function test_update_user_with_roles(): void
    {
        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Manager']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $this->tenantUser->email,
            'roles' => [$role1->name, $role2->name],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $this->tenantUser->refresh();
        $this->assertTrue($this->tenantUser->hasRole($role1->name));
        $this->assertTrue($this->tenantUser->hasRole($role2->name));
    }

    public function test_update_user_removes_unchecked_roles(): void
    {
        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Manager']);

        $this->tenantUser->assignRole([$role1->name, $role2->name]);

        $updateData = [
            'name' => $this->tenantUser->name,
            'email' => $this->tenantUser->email,
            'roles' => [$role1->name],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $this->tenantUser->refresh();
        $this->assertTrue($this->tenantUser->hasRole('Admin'));
        $this->assertFalse($this->tenantUser->hasRole('Manager'));
    }

    public function test_update_user_removes_all_roles_when_none_selected(): void
    {
        $role = Role::create(['name' => 'Admin']);
        $this->tenantUser->assignRole($role);

        $updateData = [
            'name' => $this->tenantUser->name,
            'email' => $this->tenantUser->email,
            'roles' => [],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $this->tenantUser->refresh();
        $this->assertCount(0, $this->tenantUser->roles);
    }

    public function test_update_user_can_add_new_roles(): void
    {
        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Manager']);

        $this->tenantUser->assignRole($role1);

        $updateData = [
            'name' => $this->tenantUser->name,
            'email' => $this->tenantUser->email,
            'roles' => [$role1->name, $role2->name],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put(route($this->route, [$this->tenant, $this->tenantUser]), $updateData);

        $this->tenantUser->refresh();
        $this->assertTrue($this->tenantUser->hasRole($role1->name));
        $this->assertTrue($this->tenantUser->hasRole($role2->name));
        $this->assertCount(2, $this->tenantUser->roles);
    }
}
