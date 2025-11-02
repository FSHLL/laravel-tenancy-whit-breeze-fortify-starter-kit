<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Enums\Permissions;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateUserTest extends TestCase
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

        $this->authenticatedUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->targetUser = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('original-password'),
            'tenant_id' => $this->tenant->id,
        ]);

        // Create permission and assign to user
        $permission = Permission::create(['name' => Permissions::UPDATE_TENANT_USER_BY_TENANT->value, 'tenant_id' => $this->tenant->id]);
        $role = Role::create(['name' => 'Test Role', 'tenant_id' => $this->tenant->id]);
        $role->givePermissionTo($permission);
        $this->authenticatedUser->assignRole($role);

        $this->route = "http://{$this->tenant->id}.localhost/users/{$this->targetUser->id}";
    }

    public function test_authenticated_user_can_update_user(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect(route('users.show', $this->targetUser))
            ->assertSessionHas('success', 'User updated successfully.');
    }

    public function test_user_without_permission_cannot_update_user(): void
    {
        $userWithoutPermission = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->actingAs($userWithoutPermission)
            ->put($this->route, $updateData);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_user(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $this->put($this->route, $updateData)
            ->assertRedirect(route('login'));
    }

    public function test_update_user_requires_name(): void
    {
        $updateData = [
            'email' => 'updated@example.com',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('name');
    }

    public function test_update_user_requires_email(): void
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('email');
    }

    public function test_update_user_requires_valid_email(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'invalid-email',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('email');
    }

    public function test_update_user_requires_unique_email(): void
    {
        $otherUser = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $otherUser->email,
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('email');
    }

    public function test_update_user_can_keep_same_email(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => $this->targetUser->email, // Same email
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'name' => 'Updated Name',
            'email' => $this->targetUser->email,
        ]);

        $response->assertRedirect(route('users.show', $this->targetUser));
    }

    public function test_update_user_with_password(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertTrue(Hash::check('new-password123', $this->targetUser->password));
    }

    public function test_update_user_without_password_keeps_existing(): void
    {
        $originalPassword = $this->targetUser->password;

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertEquals($originalPassword, $this->targetUser->password);
    }

    public function test_update_user_password_requires_confirmation(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('password');
    }

    public function test_update_user_password_requires_minimum_length(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('password');
    }

    public function test_update_user_email_resets_verification_when_changed(): void
    {
        $this->assertTrue($this->targetUser->hasVerifiedEmail());

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertNull($this->targetUser->email_verified_at);
    }

    public function test_update_user_email_keeps_verification_when_unchanged(): void
    {
        $originalVerification = $this->targetUser->email_verified_at;

        $updateData = [
            'name' => 'Updated Name',
            'email' => $this->targetUser->email, // Same email
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertEquals($originalVerification, $this->targetUser->email_verified_at);
    }

    public function test_update_user_hashes_new_password(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertNotEquals('new-password123', $this->targetUser->password);
        $this->assertTrue(Hash::check('new-password123', $this->targetUser->password));
    }

    public function test_update_nonexistent_user_returns_404(): void
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put(str_replace($this->targetUser->id, 99999, $this->route), $updateData)
            ->assertNotFound();
    }

    public function test_update_user_with_long_name_fails(): void
    {
        $updateData = [
            'name' => str_repeat('a', 256), // Over typical varchar limit
            'email' => 'updated@example.com',
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData)
            ->assertSessionHasErrors('name');
    }

    public function test_update_user_only_updates_allowed_fields(): void
    {
        $originalCreatedAt = $this->targetUser->created_at;

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'created_at' => now()->addDay(), // Try to update created_at
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertEquals('Updated Name', $this->targetUser->name);
        $this->assertEquals('updated@example.com', $this->targetUser->email);
        $this->assertEquals($originalCreatedAt, $this->targetUser->created_at); // Should not change
    }

    public function test_update_user_with_roles(): void
    {
        $role1 = Role::factory()->create(['name' => 'Admin']);
        $role2 = Role::factory()->create(['name' => 'Manager']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $this->targetUser->email,
            'roles' => [$role1->name, $role2->name],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertTrue($this->targetUser->hasRole($role1->name));
        $this->assertTrue($this->targetUser->hasRole($role2->name));
    }

    public function test_update_user_removes_unchecked_roles(): void
    {
        $role1 = Role::factory()->create(['name' => 'Admin']);
        $role2 = Role::factory()->create(['name' => 'Manager']);

        $this->targetUser->assignRole([$role1->name, $role2->name]);

        $updateData = [
            'name' => $this->targetUser->name,
            'email' => $this->targetUser->email,
            'roles' => [$role1->name],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertTrue($this->targetUser->hasRole($role1->name));
        $this->assertFalse($this->targetUser->hasRole($role2->name));
    }

    public function test_update_user_removes_all_roles_when_none_selected(): void
    {
        $role = Role::factory()->create(['name' => 'Admin']);
        $this->targetUser->assignRole($role);

        $updateData = [
            'name' => $this->targetUser->name,
            'email' => $this->targetUser->email,
            'roles' => [],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertCount(0, $this->targetUser->roles);
    }

    public function test_update_user_can_add_new_roles(): void
    {
        $role1 = Role::factory()->create(['name' => 'Admin']);
        $role2 = Role::factory()->create(['name' => 'Manager']);

        $this->targetUser->assignRole($role1->name);

        $updateData = [
            'name' => $this->targetUser->name,
            'email' => $this->targetUser->email,
            'roles' => [$role1->name, $role2->name],
        ];

        $this->actingAs($this->authenticatedUser)
            ->put($this->route, $updateData);

        $this->targetUser->refresh();
        $this->assertTrue($this->targetUser->hasRole($role1->name));
        $this->assertTrue($this->targetUser->hasRole($role2->name));
        $this->assertCount(2, $this->targetUser->roles);
    }
}
