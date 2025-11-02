<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Enums\CentralPermissions;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DestroyTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $route = 'tenants.destroy';

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->tenant = Tenant::create(['id' => 'test-tenant']);
        $this->tenant->domains()->create(['domain' => 'test.example.com']);

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::DELETE_TENANT->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $this->user->assignRole($role);
    }

    public function test_guest_cannot_destroy_tenant(): void
    {
        $response = $this->delete(route($this->route, $this->tenant), [
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertModelExists($this->tenant);
    }

    public function test_user_without_permission_cannot_destroy_tenant(): void
    {
        $userWithoutPermission = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($userWithoutPermission)
            ->delete(route($this->route, $this->tenant), [
                'password' => 'password123',
            ]);

        $response->assertStatus(403);
        $this->assertModelExists($this->tenant);
    }

    public function test_authenticated_user_can_destroy_tenant_with_correct_password(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.index'));
        $this->assertModelMissing($this->tenant);
    }

    public function test_destroy_tenant_requires_password(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant)
        );

        $response->assertSessionHasErrors();
        $this->assertModelExists($this->tenant);
    }

    public function test_destroy_tenant_requires_correct_password(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'wrong-password']
        );

        $response->assertSessionHasErrors();
        $this->assertModelExists($this->tenant);
    }

    public function test_destroy_tenant_validates_current_user_password(): void
    {
        User::factory()->create([
            'password' => Hash::make('other-password'),
        ]);

        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'other-password']
        );

        $response->assertSessionHasErrors();
        $this->assertModelExists($this->tenant);
    }

    public function test_destroy_tenant_with_empty_password(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => '']
        );

        $response->assertSessionHasErrors();
        $this->assertModelExists($this->tenant);
    }

    public function test_destroy_tenant_deletes_associated_domains(): void
    {
        $this->tenant->domains()->create(['domain' => 'secondary.example.com']);
        $this->tenant->domains()->create(['domain' => 'third.example.com']);

        $this->assertCount(3, $this->tenant->fresh()->domains);

        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.index'));

        // Verify tenant and all domains are deleted
        $this->assertModelMissing($this->tenant);
        $this->assertDatabaseMissing('domains', ['tenant_id' => $this->tenant->id]);
    }

    public function test_destroy_tenant_redirects_to_intended_route(): void
    {
        session(['url.intended' => route('tenants.create')]);

        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.create'));
    }

    public function test_destroy_tenant_redirects_to_tenants_index_by_default(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.index'));
    }

    public function test_destroy_nonexistent_tenant_returns_404(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, 'nonexistent-tenant'),
            ['password' => 'password123']
        );

        $response->assertNotFound();
    }

    public function test_destroy_tenant_with_special_characters_in_password(): void
    {
        $userWithSpecialPassword = User::factory()->create([
            'password' => Hash::make('p@ssw0rd!#$%'),
        ]);

        $userWithSpecialPassword->syncPermissions([CentralPermissions::DELETE_TENANT->value]);

        $response = $this->actingAs($userWithSpecialPassword)->delete(
            route($this->route, $this->tenant),
            ['password' => 'p@ssw0rd!#$%']
        );

        $response->assertRedirect(route('tenants.index'));
        $this->assertModelMissing($this->tenant);
    }

    public function test_destroy_tenant_password_validation_is_case_sensitive(): void
    {
        $userWithPassword = User::factory()->create([
            'password' => Hash::make('Password123'),
        ]);

        $userWithPassword->syncPermissions([CentralPermissions::DELETE_TENANT->value]);

        $response = $this->actingAs($userWithPassword)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123'] // lowercase
        );

        $response->assertSessionHasErrors();
        $this->assertModelExists($this->tenant);

        $newTenant = Tenant::create(['id' => 'test-tenant-2']);

        $response = $this->actingAs($userWithPassword)->delete(
            route($this->route, $newTenant),
            ['password' => 'Password123'] // correct case
        );

        $response->assertRedirect(route('tenants.index'));
        $this->assertModelMissing($newTenant);
    }

    public function test_destroy_tenant_preserves_other_tenants(): void
    {
        $otherTenant = Tenant::create(['id' => 'other-tenant']);
        $otherTenant->domains()->create(['domain' => 'other.example.com']);

        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.index'));

        // Original tenant should be deleted
        $this->assertModelMissing($this->tenant);

        // Other tenant should remain
        $this->assertModelExists($otherTenant);
        $this->assertDatabaseHas('domains', ['tenant_id' => $otherTenant->id]);
    }

    public function test_destroy_tenant_with_numeric_password(): void
    {
        $userWithNumericPassword = User::factory()->create([
            'password' => Hash::make('123456789'),
        ]);

        $userWithNumericPassword->syncPermissions([CentralPermissions::DELETE_TENANT->value]);

        $response = $this->actingAs($userWithNumericPassword)->delete(
            route($this->route, $this->tenant),
            ['password' => '123456789']
        );

        $response->assertRedirect(route('tenants.index'));
        $this->assertModelMissing($this->tenant);
    }

    public function test_destroy_tenant_password_cannot_be_null(): void
    {
        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => null]
        );

        $response->assertSessionHasErrors();
        $this->assertModelExists($this->tenant);
    }

    public function test_destroy_tenant_with_long_password(): void
    {
        $longPassword = str_repeat('a', 100);
        $userWithLongPassword = User::factory()->create([
            'password' => Hash::make($longPassword),
        ]);

        $userWithLongPassword->syncPermissions([CentralPermissions::DELETE_TENANT->value]);

        $response = $this->actingAs($userWithLongPassword)->delete(
            route($this->route, $this->tenant),
            ['password' => $longPassword]
        );

        $response->assertRedirect(route('tenants.index'));
        $this->assertModelMissing($this->tenant);
    }

    public function test_destroy_tenant_cascade_deletes_domains(): void
    {
        $domainId = $this->tenant->domains()->first()->id;

        $response = $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.index'));

        $this->assertModelMissing($this->tenant);
        $this->assertDatabaseMissing('domains', ['id' => $domainId]);
    }

    public function test_destroy_tenant_clears_session_errors_on_success(): void
    {
        $this->actingAs($this->user)->delete(
            route($this->route, $this->tenant),
            ['password' => 'wrong-password']
        )->assertSessionHasErrors();

        $newTenant = Tenant::create(['id' => 'test-tenant-clean']);

        $response = $this->actingAs($this->user)->delete(
            route($this->route, $newTenant),
            ['password' => 'password123']
        );

        $response->assertRedirect(route('tenants.index'));
        $response->assertSessionDoesntHaveErrors();
    }
}
