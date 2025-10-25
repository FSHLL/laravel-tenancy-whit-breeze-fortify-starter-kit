<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTenantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tenant = Tenant::create(['id' => 'test-tenant']);
        $this->tenant->domains()->create(['domain' => 'test.example.com']);
    }

    public function test_guest_cannot_update_tenant(): void
    {
        $response = $this->put(route('tenants.update', $this->tenant), [
            'domains' => ['updated.example.com'],
            'data' => json_encode(['name' => 'Updated Tenant']),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_tenant_successfully(): void
    {
        $updateData = [
            'domains' => ['updated.example.com', 'secondary.example.com'],
            'data' => json_encode([
                'name' => 'Updated Tenant Name',
                'description' => 'Updated description',
                'plan' => 'premium',
            ]),
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();

        $this->assertEquals('Updated Tenant Name', $this->tenant->name);
        $this->assertEquals('Updated description', $this->tenant->description);
        $this->assertEquals('premium', $this->tenant->plan);

        $this->assertCount(2, $this->tenant->domains);
        $this->assertTrue($this->tenant->domains->pluck('domain')->contains('updated.example.com'));
        $this->assertTrue($this->tenant->domains->pluck('domain')->contains('secondary.example.com'));
    }

    public function test_update_tenant_replaces_all_domains(): void
    {
        $this->tenant->domains()->create(['domain' => 'second.example.com']);
        $this->assertCount(2, $this->tenant->fresh()->domains);

        $updateData = [
            'domains' => ['new-only.example.com'],
            'data' => json_encode(['name' => 'Updated Tenant']),
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();

        $this->assertCount(1, $this->tenant->domains);
        $this->assertEquals('new-only.example.com', $this->tenant->domains->first()->domain);
    }

    public function test_update_tenant_with_null_data(): void
    {
        $updateData = [
            'domains' => ['updated.example.com'],
            'data' => null,
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();
        $this->assertCount(1, $this->tenant->domains);
        $this->assertEquals('updated.example.com', $this->tenant->domains->first()->domain);
    }

    public function test_update_tenant_with_empty_data(): void
    {
        $updateData = [
            'domains' => ['updated.example.com'],
            'data' => '',
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();
        $this->assertCount(1, $this->tenant->domains);
    }

    public function test_update_tenant_preserves_tenant_id(): void
    {
        $originalId = $this->tenant->id;

        $updateData = [
            'domains' => ['updated.example.com'],
            'data' => json_encode(['name' => 'Updated Tenant']),
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();
        $this->assertEquals($originalId, $this->tenant->id);
    }

    public function test_update_tenant_validation_requires_domains(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertSessionHasErrors('domains');
    }

    public function test_update_tenant_validation_requires_at_least_one_domain(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => [],
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertSessionHasErrors('domains');
    }

    public function test_update_tenant_validation_domains_must_be_strings(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => [123, 'valid.example.com'],
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertSessionHasErrors('domains.0');
    }

    public function test_update_tenant_validation_domains_must_be_valid_format(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => ['invalid domain with spaces'],
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertSessionHasErrors('domains.0');
    }

    public function test_update_tenant_validation_domains_must_be_unique(): void
    {
        $otherTenant = Tenant::create(['id' => 'other-tenant']);
        $otherTenant->domains()->create(['domain' => 'existing.example.com']);

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => ['existing.example.com'],
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertSessionHasErrors('domains.0');
    }

    public function test_update_tenant_can_keep_existing_domains(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => ['test.example.com'],
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertRedirect(route('tenants.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_update_tenant_validation_data_must_be_valid_json(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => ['updated.example.com'],
                'data' => 'invalid json string',
            ]
        );

        $response->assertSessionHasErrors('data');
    }

    public function test_update_tenant_validation_domains_max_length(): void
    {
        $longDomain = str_repeat('a', 255).'.com';

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => [$longDomain],
                'data' => json_encode(['name' => 'Updated Tenant']),
            ]
        );

        $response->assertSessionHasErrors('domains.0');
    }

    public function test_update_tenant_removes_custom_columns_from_data(): void
    {
        $updateData = [
            'domains' => ['updated.example.com'],
            'data' => json_encode([
                'name' => 'Updated Tenant',
                'custom_field' => 'custom_value',
            ]),
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();
        $this->assertEquals('Updated Tenant', $this->tenant->name);
        $this->assertEquals('custom_value', $this->tenant->custom_field);
    }

    public function test_update_tenant_with_complex_data(): void
    {
        $complexData = [
            'name' => 'Complex Tenant',
            'settings' => [
                'theme' => 'dark',
                'notifications' => true,
                'features' => ['feature1', 'feature2'],
            ],
            'metadata' => [
                'version' => '2.0',
                'last_updated' => '2025-01-01',
            ],
        ];

        $updateData = [
            'domains' => ['complex.example.com'],
            'data' => json_encode($complexData),
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.index'));

        $this->tenant->refresh();
        $this->assertEquals('Complex Tenant', $this->tenant->name);
        $this->assertEquals('dark', $this->tenant->settings['theme']);
        $this->assertTrue($this->tenant->settings['notifications']);
        $this->assertEquals(['feature1', 'feature2'], $this->tenant->settings['features']);
    }

    public function test_update_tenant_redirects_to_intended_route(): void
    {
        session(['url.intended' => route('tenants.show', $this->tenant)]);

        $updateData = [
            'domains' => ['updated.example.com'],
            'data' => json_encode(['name' => 'Updated Tenant']),
        ];

        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            $updateData
        );

        $response->assertRedirect(route('tenants.show', $this->tenant));
    }

    public function test_update_tenant_with_multiple_validation_errors(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('tenants.update', $this->tenant),
            [
                'domains' => [], // Empty domains
                'data' => 'invalid json', // Invalid JSON
            ]
        );

        $response->assertSessionHasErrors(['domains', 'data']);
    }
}
