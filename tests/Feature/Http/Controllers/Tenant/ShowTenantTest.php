<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $route = 'tenants.show';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /** @test */
    public function it_can_display_tenant_details_page(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.show');
        $response->assertViewHas('tenant');
    }

    /** @test */
    public function it_displays_basic_tenant_information(): void
    {
        $tenant = Tenant::create(['id' => 'basic-info-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Tenant Details');
        $response->assertSee('Basic Information');
        $response->assertSee('basic-info-tenant');
        $response->assertSee('Active');
        $response->assertSee($tenant->created_at->toDayDateTimeString());
        $response->assertSee($tenant->updated_at->toDayDateTimeString());
    }

    /** @test */
    public function it_displays_tenant_with_domains(): void
    {
        $tenant = Tenant::create(['id' => 'tenant-with-domains']);
        $tenant->domains()->createMany([
            ['domain' => 'domain1.example.com'],
            ['domain' => 'domain2.example.com'],
            ['domain' => 'subdomain.test.org'],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Domains');
        $response->assertSee('All domains associated with this tenant');
        $response->assertSee('domain1.example.com');
        $response->assertSee('domain2.example.com');
        $response->assertSee('subdomain.test.org');
        $response->assertSee('Added');
    }

    /** @test */
    public function it_displays_empty_domains_state(): void
    {
        $tenant = Tenant::create(['id' => 'tenant-no-domains']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Domains');
        $response->assertSee('No domains');
        $response->assertSee('This tenant has no domains configured');
    }

    /** @test */
    public function it_displays_additional_data_when_present(): void
    {
        $tenant = Tenant::create([
            'id' => 'tenant-with-data',
            'setting1' => 'value1',
            'setting2' => 'value2',
            'config' => ['nested' => true],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Additional Data');
        $response->assertSee('Custom configuration and metadata');
        $response->assertSee('setting1');
        $response->assertSee('value1');
        $response->assertSee('setting2');
        $response->assertSee('value2');
    }

    /** @test */
    public function it_does_not_display_additional_data_section_when_empty(): void
    {
        $tenant = Tenant::create(['id' => 'tenant-no-data']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertDontSee('Additional Data');
        $response->assertDontSee('Custom configuration and metadata');
    }

    /** @test */
    public function it_displays_action_buttons(): void
    {
        $tenant = Tenant::create(['id' => 'action-buttons-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Back to List');
        $response->assertSee('Edit');
        $response->assertSee(route('tenants.index'));
        $response->assertSee(route('tenants.edit', $tenant));
    }

    /** @test */
    public function it_displays_danger_zone_section(): void
    {
        $tenant = Tenant::create(['id' => 'danger-zone-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Danger Zone');
        $response->assertSee('Irreversible and destructive actions');
        $response->assertSee('Delete this tenant');
        $response->assertSee('Once you delete a tenant, there is no going back');
        $response->assertSee('Delete tenant');
    }

    /** @test */
    public function it_shows_delete_confirmation_modal(): void
    {
        $tenant = Tenant::create(['id' => 'modal-test-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('confirm-tenant-deletion-'.$tenant->id);
    }

    /** @test */
    public function it_returns_404_for_non_existent_tenant(): void
    {
        $response = $this->get(route($this->route, 'non-existent-tenant'));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $tenant = Tenant::create(['id' => 'auth-test-tenant']);

        $this->post(route('logout'));

        $response = $this->get(route($this->route, $tenant));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_displays_correct_page_structure(): void
    {
        $tenant = Tenant::create(['id' => 'structure-test-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Basic Information');
        $response->assertSee('Domains');
        $response->assertSee('Danger Zone');
    }

    /** @test */
    public function it_displays_tenant_with_complex_additional_data(): void
    {
        $tenant = Tenant::create([
            'id' => 'complex-data-tenant',
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
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Additional Data');
        $response->assertSee('database');
        $response->assertSee('localhost');
        $response->assertSee('features');
        $response->assertSee('limits');
        $response->assertSee('10GB');
    }

    /** @test */
    public function it_displays_domain_creation_timestamps(): void
    {
        $tenant = Tenant::create(['id' => 'timestamp-test-tenant']);
        $domain = $tenant->domains()->create(['domain' => 'timestamp.example.com']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('timestamp.example.com');
        $response->assertSee('Added');
        $response->assertSee($domain->created_at->diffForHumans());
    }

    /** @test */
    public function it_passes_tenant_with_loaded_domains_to_view(): void
    {
        $tenant = Tenant::create(['id' => 'view-data-tenant']);
        $tenant->domains()->create(['domain' => 'loaded.example.com']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);

        $viewTenant = $response->viewData('tenant');
        $this->assertEquals('view-data-tenant', $viewTenant->id);
        $this->assertTrue($viewTenant->relationLoaded('domains'));
        $this->assertCount(1, $viewTenant->domains);
    }
}
