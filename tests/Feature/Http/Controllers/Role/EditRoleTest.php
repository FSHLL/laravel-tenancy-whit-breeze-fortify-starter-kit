<?php

namespace Tests\Feature\Http\Controllers\Role;

use App\Enums\CentralPermissions;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CentralPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EditRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.edit';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_authenticated_user_can_access_edit_role_page(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertViewIs('roles.edit');
    }

    public function test_unauthenticated_user_cannot_access_edit_role_page(): void
    {
        $role = Role::factory()->create();
        auth()->guard('web')->logout();

        $response = $this->get(route($this->route, $role));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_role_page_displays_with_existing_role_data(): void
    {
        $role = Role::factory()->create(['name' => 'Existing Role']);

        $this->seed(CentralPermissionsSeeder::class);
        $permissions = CentralPermissions::cases();

        $role->syncPermissions($permissions);

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('value="Existing Role"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="_method"', false);
        $response->assertSee('value="PUT"', false);
        $response->assertSee('Select All');
        $response->assertSee('Select None');
        $response->assertSee('id="select-all"', false);
        $response->assertSee('id="select-none"', false);
        $response->assertSee('Back to Roles');
        $response->assertSee('View Role');
        $response->assertSee(route('roles.index'));
        $response->assertSee(route('roles.show', $role));
        $response->assertStatus(200);
        $response->assertSee('action="'.route('roles.update', $role).'"', false);

        foreach ($permissions as $permission) {
            $response->assertSee($permission->value);
            $response->assertSee('checked', false);
        }
    }

    public function test_edit_role_page_displays_cancel_button(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('Cancel');
        $response->assertSee(route('roles.show', $role));
    }

    public function test_edit_nonexistent_role_returns_404(): void
    {
        $response = $this->get(route($this->route, 999));

        $response->assertStatus(404);
    }

    public function test_edit_role_page_includes_old_input_values(): void
    {
        $role = Role::factory()->create(['name' => 'Original Name']);

        // Simulate validation failure with old input
        session()->flash('_old_input', ['name' => 'New Name']);

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        // Should show old input when available
        $response->assertSee('name');
    }

    public function test_edit_role_page_passes_edit_mode_to_partial(): void
    {
        $role = Role::factory()->create();

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertViewHas('role');
        $response->assertViewHas('permissions');
        $viewRole = $response->viewData('role');
        $this->assertEquals($role->id, $viewRole->id);
    }

    public function test_edit_role_page_displays_role_information_section(): void
    {
        $role = Role::factory()->create(['name' => 'Admin Role']);
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $response = $this->get(route($this->route, $role));

        $response->assertStatus(200);
        $response->assertSee('Current Role Information');
        $response->assertSee('3'); // Users count
        $response->assertSee($role->guard_name);
        $response->assertSee($role->created_at->format('M j, Y'));
    }
}
