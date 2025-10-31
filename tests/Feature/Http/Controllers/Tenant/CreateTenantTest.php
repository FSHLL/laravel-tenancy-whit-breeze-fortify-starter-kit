<?php

namespace Tests\Feature\Http\Controllers\Tenant;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTenantTest extends TestCase
{
    use RefreshDatabase;

    private string $route = 'tenants.create';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear y autenticar un usuario para todas las pruebas
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_it_can_display_create_tenant_page(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewIs('tenants.create');
    }

    public function test_it_displays_correct_page_title(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Create Tenant');
    }

    public function test_it_displays_back_to_list_button(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Back to List');
        $response->assertSee(route('tenants.index'));
    }

    public function test_it_includes_tenant_form_partial(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Tenant ID');
        $response->assertSee('Domains');
        $response->assertSee('Additional Data (JSON) - Optional');
    }

    public function test_it_displays_tenant_id_field_with_correct_attributes(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('name="id"', false);
        $response->assertSee('required', false);
        $response->assertSee('placeholder="unique-tenant-id"', false);
        $response->assertSee('A unique identifier for this tenant');
    }

    public function test_it_displays_domains_field_with_alpine_js(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('x-data', false);
        $response->assertSee('domains:', false);
        $response->assertSee('name="domains[]"', false);
        $response->assertSee('Add Domain');
        $response->assertSee('placeholder="example.com"', false);
    }

    public function test_it_displays_additional_data_textarea(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('name="data"', false);
        $response->assertSee('rows="4"', false);
        $response->assertSee('placeholder=\'{"key": "value"}\'', false);
        $response->assertSee('Optional custom data in JSON format');
    }

    public function test_it_displays_form_with_correct_action_and_method(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('action="'.route('tenants.store').'"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="_token"', false); // CSRF token
    }

    public function test_it_displays_action_buttons(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Cancel');
        $response->assertSee('Create Tenant');
        $response->assertSee(route('tenants.index')); // Cancel button link
    }

    public function test_it_displays_form_in_creation_mode(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        // Verificar que está en modo creación (no edición)
        $response->assertDontSee('readonly', false);
        $response->assertDontSee('Tenant ID cannot be changed after creation');
        $response->assertSee('A unique identifier for this tenant');
        $response->assertSee('Create Tenant'); // Submit button text
        $response->assertDontSee('Update Tenant');
    }

    public function test_it_displays_help_text_for_fields(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Use lowercase letters, numbers, and hyphens only');
        $response->assertSee('Optional custom data in JSON format');
    }

    public function test_it_displays_remove_domain_buttons(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('domains.splice(index, 1)', false);
        $response->assertSee('x-show="domains.length > 1"', false);
    }

    public function test_it_displays_add_domain_button(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('domains.push(\'\')', false);
        $response->assertSee('Add Domain');
    }

    public function test_it_requires_authentication(): void
    {
        $this->post(route('logout'));

        $response = $this->get(route($this->route));

        $response->assertRedirect(route('login'));
    }

    public function test_it_displays_proper_form_styling(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('space-y-6', false); // Form spacing
        $response->assertSee('bg-white dark:bg-gray-800', false); // Card background
        $response->assertSee('shadow-sm sm:rounded-lg', false); // Card styling
    }

    public function test_it_uses_app_layout(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        // Verificar que usa el layout de la aplicación
        $response->assertSee('max-w-7xl mx-auto', false);
        $response->assertSee('py-12', false);
    }

    public function test_it_displays_svg_icons(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        // Verificar presencia de iconos SVG
        $response->assertSee('<svg', false);
        $response->assertSee('stroke="currentColor"', false);
    }

    public function test_it_shows_default_empty_domain_field(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('domains: [&quot;&quot;]', false);
    }

    public function test_it_displays_proper_input_labels(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('Tenant ID');
        $response->assertSee('Domains');
        $response->assertSee('Additional Data (JSON) - Optional');
    }

    public function test_it_has_proper_form_structure(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('<form', false);
        $response->assertSee('</form>', false);
        $response->assertSee('type="text"', false);
        $response->assertSee('type="submit"', false);
    }
}
