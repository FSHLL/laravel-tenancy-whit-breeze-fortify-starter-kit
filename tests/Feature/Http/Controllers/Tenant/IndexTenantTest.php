<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Enums\CentralPermissions;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $route = 'tenants.index';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Create permission and assign to user
        $permission = Permission::create(['name' => CentralPermissions::VIEW_TENANT->value]);
        $role = Role::create(['name' => 'Test Role']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
    }

    public function test_it_can_display_the_tenants_index_page(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.index');
        $response->assertViewHas('tenants');
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route($this->route));

        $response->assertStatus(403);
    }

    public function test_it_displays_empty_state_when_no_tenants_exist(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('No tenants');
        $response->assertSee('Get started by creating a new tenant.');
        $response->assertSee('Create your first tenant');
    }

    public function test_it_displays_tenants_list_when_tenants_exist(): void
    {
        $tenant1 = Tenant::create(['id' => 'tenant-1']);
        $tenant1->domains()->create(['domain' => 'tenant1.example.com']);

        $tenant2 = Tenant::create(['id' => 'tenant-2']);
        $tenant2->domains()->create(['domain' => 'tenant2.example.com']);

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('tenant-1');
        $response->assertSee('tenant-2');
        $response->assertSee('tenant1.example.com');
        $response->assertSee('tenant2.example.com');
        $response->assertSee('Active');
        $response->assertDontSee('No tenants');
    }

    public function test_it_displays_tenant_without_domains(): void
    {
        Tenant::create(['id' => 'tenant-without-domains']);

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('tenant-without-domains');
        $response->assertSee('No domains');
    }

    public function test_it_displays_tenant_with_multiple_domains(): void
    {
        $tenant = Tenant::create(['id' => 'multi-domain-tenant']);
        $tenant->domains()->createMany([
            ['domain' => 'domain1.example.com'],
            ['domain' => 'domain2.example.com'],
            ['domain' => 'domain3.example.com'],
        ]);

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('multi-domain-tenant');
        $response->assertSee('domain1.example.com');
        $response->assertSee('domain2.example.com');
        $response->assertSee('domain3.example.com');
    }

    public function test_it_displays_correct_created_date_format(): void
    {
        $tenant = Tenant::create(['id' => 'date-test-tenant']);

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee($tenant->created_at->toDayDateTimeString());
    }

    public function test_it_shows_action_buttons_for_each_tenant(): void
    {
        $tenant = Tenant::create(['id' => 'action-test-tenant']);

        $response = $this->get(route($this->route));

        $response->assertStatus(200);

        $response->assertSee('View', false);
        $response->assertSee('Edit', false);
        $response->assertSee(route('tenants.show', $tenant));
        $response->assertSee(route('tenants.edit', $tenant));
    }

    public function test_it_displays_create_tenant_button(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Create Tenant');
        $response->assertSee(route('tenants.create'));
    }

    public function test_it_uses_pagination(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Tenant::create(['id' => "tenant-{$i}"]);
        }

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewHas('tenants');

        $tenants = $response->viewData('tenants');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $tenants);
    }

    public function test_it_requires_authentication(): void
    {
        $this->post(route('logout'));

        $response = $this->get(route($this->route));

        $response->assertRedirect(route('login'));
    }

    public function test_it_displays_correct_page_title(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Tenant Management');
    }
}
