<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Enums\CentralPermissions;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $route = 'tenants.store';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::CREATE_TENANT->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
    }

    public function test_it_can_create_a_tenant_with_valid_data(): void
    {
        $tenantData = [
            'id' => 'test-tenant',
            'domains' => ['test.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', ['id' => 'test-tenant']);
        $this->assertDatabaseHas('domains', ['domain' => 'test.example.com']);
    }

    public function test_it_creates_tenant_permissions_and_roles(): void
    {
        $tenantData = [
            'id' => 'permissions-tenant',
            'domains' => ['permissions.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));

        $tenant = Tenant::find('permissions-tenant');
        $this->assertNotNull($tenant);

        // Verify permissions were created for the tenant
        $tenant->run(function () {
            $permissionsCount = count(Permissions::cases());
            $this->assertEquals($permissionsCount, Permission::count());

            foreach (Permissions::cases() as $permission) {
                $this->assertDatabaseHas('permissions', [
                    'name' => $permission->value,
                ]);
            }
        });
    }

    public function test_it_creates_tenant_roles_with_permissions(): void
    {
        $tenantData = [
            'id' => 'roles-tenant',
            'domains' => ['roles.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));

        $tenant = Tenant::find('roles-tenant');
        $this->assertNotNull($tenant);

        // Verify roles were created with correct permissions
        $tenant->run(function () {
            $rolesCount = count(Roles::cases());
            $this->assertEquals($rolesCount, Role::count());

            foreach (Roles::cases() as $roleEnum) {
                $role = Role::where('name', $roleEnum->value)->first();
                $this->assertNotNull($role);

                $expectedPermissions = Permissions::byRole($roleEnum);
                $this->assertCount(count($expectedPermissions), $role->permissions);

                foreach ($expectedPermissions as $permission) {
                    $this->assertTrue($role->hasPermissionTo($permission));
                }
            }
        });
    }

    public function test_user_without_permission_cannot_store_tenant(): void
    {
        $userWithoutPermission = User::factory()->create();

        $tenantData = [
            'id' => 'test-tenant',
            'domains' => ['test.example.com'],
        ];

        $response = $this->actingAs($userWithoutPermission)
            ->post(route($this->route), $tenantData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tenants', ['id' => 'test-tenant']);
    }

    public function test_it_can_create_tenant_with_multiple_domains(): void
    {
        $tenantData = [
            'id' => 'multi-domain-tenant',
            'domains' => [
                'domain1.example.com',
                'domain2.example.com',
                'subdomain.test.org',
            ],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', ['id' => 'multi-domain-tenant']);
        $this->assertDatabaseHas('domains', ['domain' => 'domain1.example.com']);
        $this->assertDatabaseHas('domains', ['domain' => 'domain2.example.com']);
        $this->assertDatabaseHas('domains', ['domain' => 'subdomain.test.org']);
    }

    public function test_it_can_create_tenant_with_additional_data(): void
    {
        $additionalData = [
            'setting1' => 'value1',
            'setting2' => 'value2',
            'config' => ['nested' => true],
        ];

        $tenantData = [
            'id' => 'tenant-with-data',
            'domains' => ['data.example.com'],
            'data' => json_encode($additionalData),
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));

        $tenant = Tenant::find('tenant-with-data');
        $this->assertNotNull($tenant);
        $this->assertEquals('value1', $tenant->setting1);
        $this->assertEquals('value2', $tenant->setting2);
        $this->assertEquals(['nested' => true], $tenant->config);
    }

    public function test_it_requires_tenant_id(): void
    {
        $tenantData = [
            'domains' => ['test.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertSessionHasErrors('id');
        $this->assertDatabaseMissing('tenants', ['id' => null]);
    }

    public function test_it_requires_at_least_one_domain(): void
    {
        $tenantData = [
            'id' => 'no-domains-tenant',
            'domains' => [],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertSessionHasErrors('domains');
        $this->assertDatabaseMissing('tenants', ['id' => 'no-domains-tenant']);
    }

    public function test_it_validates_tenant_id_uniqueness(): void
    {
        Tenant::create(['id' => 'existing-tenant']);

        $tenantData = [
            'id' => 'existing-tenant',
            'domains' => ['test.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertSessionHasErrors('id');
        $this->assertDatabaseMissing('domains', ['domain' => 'test.example.com']);
    }

    public function test_it_validates_domain_uniqueness(): void
    {
        $existingTenant = Tenant::create(['id' => 'existing-tenant']);
        $existingTenant->domains()->create(['domain' => 'existing.example.com']);

        $tenantData = [
            'id' => 'new-tenant',
            'domains' => ['existing.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertSessionHasErrors('domains.0');
        $this->assertDatabaseMissing('tenants', ['id' => 'new-tenant']);
    }

    public function test_it_validates_json_format_for_additional_data(): void
    {
        $tenantData = [
            'id' => 'invalid-json-tenant',
            'domains' => ['test.example.com'],
            'data' => '{invalid json}',
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertSessionHasErrors('data');
        $this->assertDatabaseMissing('tenants', ['id' => 'invalid-json-tenant']);
    }

    public function test_it_can_create_tenant_without_additional_data(): void
    {
        $tenantData = [
            'id' => 'no-data-tenant',
            'domains' => ['nodata.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', ['id' => 'no-data-tenant']);
        $this->assertDatabaseHas('domains', ['domain' => 'nodata.example.com']);
    }

    public function test_it_validates_domain_format(): void
    {
        $tenantData = [
            'id' => 'format-test-tenant',
            'domains' => [
                'valid.example.com',
                'invalid domain with spaces',
                'another@invalid.domain',
            ],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertSessionHasErrors(['domains.1', 'domains.2']);
        $this->assertDatabaseMissing('tenants', ['id' => 'format-test-tenant']);
    }

    public function test_it_redirects_to_tenants_index_after_successful_creation(): void
    {
        $tenantData = [
            'id' => 'redirect-test-tenant',
            'domains' => ['redirect.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));
    }

    public function test_it_requires_authentication(): void
    {
        $this->post(route('logout'));

        $tenantData = [
            'id' => 'auth-test-tenant',
            'domains' => ['auth.example.com'],
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('tenants', ['id' => 'auth-test-tenant']);
    }

    public function test_it_stores_complex_additional_data_correctly(): void
    {
        $complexData = [
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'name' => 'tenant_db',
            ],
            'features' => ['feature1', 'feature2', 'feature3'],
            'limits' => [
                'users' => 100,
                'storage' => '10GB',
            ],
            'boolean_setting' => true,
            'null_setting' => null,
        ];

        $tenantData = [
            'id' => 'complex-data-tenant',
            'domains' => ['complex.example.com'],
            'data' => json_encode($complexData),
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));

        $tenant = Tenant::find('complex-data-tenant');
        $this->assertNotNull($tenant);
        $this->assertEquals($complexData['database'], $tenant->database);
        $this->assertEquals($complexData['features'], $tenant->features);
        $this->assertEquals($complexData['limits'], $tenant->limits);
        $this->assertTrue($tenant->boolean_setting);
        $this->assertNull($tenant->null_setting);
    }

    public function test_it_handles_empty_additional_data(): void
    {
        $tenantData = [
            'id' => 'empty-data-tenant',
            'domains' => ['empty.example.com'],
            'data' => '',
        ];

        $response = $this->post(route($this->route), $tenantData);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', ['id' => 'empty-data-tenant']);
    }
}
