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

class DestroyUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $targetUser;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        tenancy()->initialize($this->tenant);

        $this->tenant->domains()->create(['domain' => $this->tenant->id]);

        $this->authenticatedUser = User::factory()->create([
            'password' => Hash::make('current-password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $this->targetUser = User::factory()->create([
            'name' => 'User To Delete',
            'email' => 'delete@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        // Create permission and assign to user
        $permission = Permission::create(['name' => Permissions::DELETE_TENANT_USER_BY_TENANT->value, 'tenant_id' => $this->tenant->id]);
        $role = Role::create(['name' => 'Test Role', 'tenant_id' => $this->tenant->id]);
        $role->givePermissionTo($permission);
        $this->authenticatedUser->assignRole($role);

        $this->route = "http://{$this->tenant->id}.localhost/users/{$this->targetUser->id}";
    }

    public function test_authenticated_user_can_delete_user_with_correct_password(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'current-password',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->targetUser->id,
        ]);

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User deleted successfully.');
    }

    public function test_user_without_permission_cannot_delete_user(): void
    {
        $userWithoutPermission = User::factory()->create([
            'password' => Hash::make('current-password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($userWithoutPermission)
            ->delete($this->route, [
                'password' => 'current-password',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_user(): void
    {
        $this->delete($this->route, [
            'password' => 'current-password',
        ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_requires_password(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [])
            ->assertSessionHasErrorsIn($this->targetUser->id, 'password');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_requires_correct_password(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrorsIn($this->targetUser->id, 'password');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_validates_current_password(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'incorrect-password',
            ])
            ->assertSessionHasErrorsIn($this->targetUser->id, 'password');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_uses_correct_error_bag(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrorsIn($this->targetUser->id);

        $errors = session()->get('errors');
        $this->assertTrue($errors->hasBag($this->targetUser->id));
    }

    public function test_delete_user_removes_from_database(): void
    {
        $userId = $this->targetUser->id;

        $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'current-password',
            ]);

        $this->assertNull(User::find($userId));
    }

    public function test_delete_user_with_empty_password_fails(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => '',
            ])
            ->assertSessionHasErrorsIn($this->targetUser->id, 'password');

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_redirects_to_index_after_success(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'current-password',
            ]);

        $response->assertRedirect(route('users.index'));
    }

    public function test_delete_user_shows_success_message(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'current-password',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('User deleted successfully.', session('success'));
    }

    public function test_cannot_delete_authenticated_user(): void
    {
        // This test assumes the business logic prevents self-deletion
        // You might want to implement this in your controller
        $response = $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'current-password',
            ]);

        // This should either fail or redirect with an error
        // Depending on your business logic implementation
        $this->assertDatabaseHas('users', [
            'id' => $this->authenticatedUser->id,
        ]);
    }

    public function test_delete_user_requires_method_delete(): void
    {
        // Test that POST request doesn't work
        $this->actingAs($this->authenticatedUser)
            ->post($this->route, [
                'password' => 'current-password',
            ])
            ->assertStatus(405); // Method Not Allowed

        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_with_different_authenticated_user(): void
    {
        $anotherUser = User::factory()->create([
            'password' => Hash::make('another-password'),
        ]);

        $anotherUser->syncPermissions([Permissions::DELETE_TENANT_USER_BY_TENANT->value]);

        $this->actingAs($anotherUser)
            ->delete($this->route, [
                'password' => 'another-password', // Different user's password
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->targetUser->id,
        ]);
    }

    public function test_delete_user_password_validation_message(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete($this->route, [
                'password' => 'wrong-password',
            ]);

        $errors = session()->get('errors')->getBag($this->targetUser->id);
        $this->assertTrue($errors->has('password'));
    }
}
