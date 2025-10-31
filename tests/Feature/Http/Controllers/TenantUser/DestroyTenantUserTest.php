<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DestroyTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $tenantUser;

    private string $route = 'tenants.users.destroy';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();
        $this->tenantUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_authenticated_user_can_delete_tenant_user_with_valid_password(): void
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertRedirect(route('tenants.users.index', $this->tenant));
        $this->assertDatabaseMissing('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_fails_with_invalid_password(): void
    {
        $password = 'ValidPassword123!';
        $wrongPassword = 'WrongPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $wrongPassword,
            ]);

        $response->assertSessionHasErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_fails_with_missing_password(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), []);

        $response->assertSessionHasErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_fails_with_empty_password()
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => '',
            ]);

        $response->assertSessionHasErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_displays_success_message()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect(route('tenants.users.index', $this->tenant));
    }

    public function test_delete_removes_user_completely_from_database()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);
        $userId = $this->tenantUser->id;

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertRedirect(route('tenants.users.index', $this->tenant));
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertNull(User::find($userId));
    }

    public function test_unauthenticated_user_cannot_delete_user()
    {
        $response = $this->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
            'password' => 'SomePassword123!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_handles_non_existent_tenant()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [999, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_handles_non_existent_user()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, 999]), [
                'password' => $password,
            ]);

        $response->assertStatus(404);
    }

    public function test_delete_validates_password_belongs_to_authenticated_user()
    {
        $otherUser = User::factory()->create();
        $otherPassword = 'OtherUserPassword123!';
        $otherUser->update(['password' => Hash::make($otherPassword)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $otherPassword,
            ]);

        $response->assertSessionHasErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_preserves_other_tenant_users()
    {
        $otherTenant = Tenant::factory()->create();
        $otherTenantUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $sameTenantUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertRedirect(route('tenants.users.index', $this->tenant));
        $this->assertDatabaseMissing('users', ['id' => $this->tenantUser->id]);
        $this->assertDatabaseHas('users', ['id' => $otherTenantUser->id]);
        $this->assertDatabaseHas('users', ['id' => $sameTenantUser->id]);
    }

    public function test_delete_cannot_delete_user_from_different_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $otherTenantUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $otherTenantUser]), [
                'password' => $password,
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $otherTenantUser->id]);
    }

    public function test_delete_prevents_sql_injection_attempts()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password."'; DROP TABLE users; --",
            ]);

        $response->assertSessionHasErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_handles_concurrent_deletion_gracefully()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $this->tenantUser->delete();

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser->id]), [
                'password' => $password,
            ]);

        $response->assertStatus(404);
    }

    public function test_delete_validates_csrf_protection()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertRedirect(route('tenants.users.index', $this->tenant));
        $this->assertDatabaseMissing('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_with_very_long_password_is_handled_gracefully()
    {
        $longPassword = str_repeat('a', 1000);
        $this->authenticatedUser->update(['password' => Hash::make('ValidPassword123!')]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $longPassword,
            ]);

        $response->assertSessionHasErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->tenantUser->id]);
    }

    public function test_delete_redirects_to_correct_tenant_users_index()
    {
        $password = 'ValidPassword123!';
        $this->authenticatedUser->update(['password' => Hash::make($password)]);

        $response = $this->actingAs($this->authenticatedUser)
            ->delete(route($this->route, [$this->tenant, $this->tenantUser]), [
                'password' => $password,
            ]);

        $response->assertRedirect(route('tenants.users.index', $this->tenant));
        $this->assertEquals(
            route('tenants.users.index', $this->tenant),
            $response->headers->get('Location')
        );
    }
}
