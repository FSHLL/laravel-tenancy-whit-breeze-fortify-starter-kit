<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $route = 'tenants.edit';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear y autenticar un usuario para todas las pruebas
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /** @test */
    public function it_can_display_edit_tenant_page(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.edit');
        $response->assertViewHas('tenant');
    }

    /** @test */
    public function it_displays_correct_page_title_with_tenant_id(): void
    {
        $tenant = Tenant::create(['id' => 'edit-title-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Edit Tenant edit-title-tenant');
    }

    /** @test */
    public function it_displays_back_to_tenant_button(): void
    {
        $tenant = Tenant::create(['id' => 'back-button-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Back to Tenant');
        $response->assertSee(route('tenants.show', $tenant));
    }

    /** @test */
    public function it_includes_tenant_form_partial_in_edit_mode(): void
    {
        $tenant = Tenant::create(['id' => 'form-partial-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Verificar elementos del formulario en modo edición
        $response->assertSee('Tenant ID');
        $response->assertSee('Domains');
        $response->assertSee('Additional Data (JSON) - Optional');
        $response->assertSee('Update Tenant'); // Submit button text
        $response->assertDontSee('Create Tenant');
    }

    /** @test */
    public function it_displays_tenant_id_field_as_readonly(): void
    {
        $tenant = Tenant::create(['id' => 'readonly-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('readonly', false);
        $response->assertSee('bg-gray-100 dark:bg-gray-700', false);
        $response->assertSee('Tenant ID cannot be changed after creation');
    }

    /** @test */
    public function it_displays_existing_domains_in_form(): void
    {
        $tenant = Tenant::create(['id' => 'domains-tenant']);
        $tenant->domains()->createMany([
            ['domain' => 'domain1.example.com'],
            ['domain' => 'domain2.example.com'],
            ['domain' => 'subdomain.test.org'],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Verificar que los dominios existentes están pre-cargados
        $response->assertSee('domain1.example.com', false);
        $response->assertSee('domain2.example.com', false);
        $response->assertSee('subdomain.test.org', false);
    }

    /** @test */
    public function it_displays_existing_additional_data(): void
    {
        $tenant = Tenant::create([
            'id' => 'data-tenant',
            'setting1' => 'value1',
            'setting2' => 'value2',
            'config' => ['nested' => true],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('setting1', false);
        $response->assertSee('value1', false);
        $response->assertSee('setting2', false);
        $response->assertSee('value2', false);
        $response->assertSee('nested', false);
    }

    /** @test */
    public function it_displays_form_with_correct_action_and_method(): void
    {
        $tenant = Tenant::create(['id' => 'action-method-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('action="'.route('tenants.update', $tenant).'"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="_method" value="PUT"', false); // Method spoofing
        $response->assertSee('name="_token"', false); // CSRF token
    }

    /** @test */
    public function it_displays_update_action_buttons(): void
    {
        $tenant = Tenant::create(['id' => 'action-buttons-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Cancel');
        $response->assertSee('Update Tenant');
        $response->assertSee(route('tenants.index')); // Cancel button link
    }

    /** @test */
    public function it_displays_form_in_edit_mode(): void
    {
        $tenant = Tenant::create(['id' => 'edit-mode-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Verificar que está en modo edición
        $response->assertSee('readonly', false); // Tenant ID readonly
        $response->assertSee('Tenant ID cannot be changed after creation');
        $response->assertSee('Update Tenant'); // Submit button text
        $response->assertDontSee('Create Tenant');
        $response->assertDontSee('A unique identifier for this tenant');
    }

    /** @test */
    public function it_displays_tenant_without_domains(): void
    {
        $tenant = Tenant::create(['id' => 'no-domains-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('domains: []', false);
    }

    /** @test */
    public function it_displays_tenant_without_additional_data(): void
    {
        $tenant = Tenant::create(['id' => 'no-data-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // El textarea debería estar vacío
        $response->assertSee('name="data"', false);
    }

    /** @test */
    public function it_displays_alpine_js_functionality_for_domains(): void
    {
        $tenant = Tenant::create(['id' => 'alpine-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Verificar funcionalidad Alpine.js
        $response->assertSee('x-data', false);
        $response->assertSee('domains.splice(index, 1)', false);
        $response->assertSee('domains.push(\'\')', false);
        $response->assertSee('x-show="domains.length > 1"', false);
        $response->assertSee('Add Domain');
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

        // Cerrar sesión del usuario
        $this->post(route('logout'));

        $response = $this->get(route($this->route, $tenant));

        // Debería redirigir al login
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_displays_proper_form_styling(): void
    {
        $tenant = Tenant::create(['id' => 'styling-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Verificar clases de Tailwind CSS importantes
        $response->assertSee('space-y-6', false); // Form spacing
        $response->assertSee('bg-white dark:bg-gray-800', false); // Card background
        $response->assertSee('shadow-sm sm:rounded-lg', false); // Card styling
    }

    /** @test */
    public function it_uses_app_layout(): void
    {
        $tenant = Tenant::create(['id' => 'layout-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('max-w-7xl mx-auto', false);
        $response->assertSee('py-12', false);
    }

    /** @test */
    public function it_passes_tenant_to_view(): void
    {
        $tenant = Tenant::create(['id' => 'view-data-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);

        $viewTenant = $response->viewData('tenant');
        $this->assertEquals('view-data-tenant', $viewTenant->id);
        $this->assertInstanceOf(Tenant::class, $viewTenant);
    }

    /** @test */
    public function it_loads_tenant_domains_for_form(): void
    {
        $tenant = Tenant::create(['id' => 'loaded-domains-tenant']);
        $tenant->domains()->createMany([
            ['domain' => 'loaded1.example.com'],
            ['domain' => 'loaded2.example.com'],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);

        $viewTenant = $response->viewData('tenant');
        $this->assertTrue($viewTenant->relationLoaded('domains'));
        $this->assertCount(2, $viewTenant->domains);
    }

    /** @test */
    public function it_displays_svg_icons_in_form(): void
    {
        $tenant = Tenant::create(['id' => 'icons-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Verificar presencia de iconos SVG
        $response->assertSee('<svg', false);
        $response->assertSee('stroke="currentColor"', false);
        $response->assertSee('Back to Tenant', false); // Back button with icon
    }

    /** @test */
    public function it_displays_help_text_for_edit_mode(): void
    {
        $tenant = Tenant::create(['id' => 'help-text-tenant']);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        $response->assertSee('Tenant ID cannot be changed after creation');
        $response->assertSee('Optional custom data in JSON format');
        $response->assertDontSee('Use lowercase letters, numbers, and hyphens only');
    }

    /** @test */
    public function it_preserves_old_input_on_validation_errors(): void
    {
        $tenant = Tenant::create(['id' => 'old-input-tenant']);
        $tenant->domains()->create(['domain' => 'original.example.com']);

        // Simular old input (como después de un error de validación)
        session()->flashInput([
            'domains' => ['new1.example.com', 'new2.example.com'],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);
        // Debería usar old input en lugar de datos del tenant
        $response->assertSee('new1.example.com', false);
        $response->assertSee('new2.example.com', false);
    }

    /** @test */
    public function it_displays_complex_additional_data_formatted(): void
    {
        $tenant = Tenant::create([
            'id' => 'complex-data-tenant',
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
            'features' => ['feature1', 'feature2'],
            'settings' => [
                'limit' => 100,
                'enabled' => true,
            ],
        ]);

        $response = $this->get(route($this->route, $tenant));

        $response->assertStatus(200);

        $response->assertSee('database', false);
        $response->assertSee('localhost', false);
        $response->assertSee('features', false);
        $response->assertSee('feature1', false);
    }
}
