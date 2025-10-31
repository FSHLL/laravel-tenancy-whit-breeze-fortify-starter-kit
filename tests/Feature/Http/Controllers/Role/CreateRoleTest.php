<?php

namespace Tests\Feature\Http\Controllers\Role;

use App\Enums\CentralPermissions;
use App\Models\User;
use Database\Seeders\CentralPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateRoleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $route = 'roles.create';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_authenticated_user_can_access_create_role_page(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewIs('roles.create');
    }

    public function test_unauthenticated_user_cannot_access_create_role_page(): void
    {
        auth()->guard('web')->logout();

        $response = $this->get(route($this->route));

        $response->assertRedirect(route('login'));
    }

    public function test_create_role_page_displays_permissions(): void
    {
        $this->seed(CentralPermissionsSeeder::class);
        $permissions = CentralPermissions::cases();

        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertViewHas('permissions');
        $response->assertSee('Select All');
        $response->assertSee('Select None');
        $response->assertSee('id="select-all"', false);
        $response->assertSee('id="select-none"', false);
        $response->assertSee('Cancel');
        $response->assertSee('Create Role');
        $response->assertSee('type="submit"', false);
        $response->assertSee('action="'.route('roles.store').'"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('Create Role');
        $response->assertSeeInOrder(['Create Role', 'Back to Roles']);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="permissions[]"', false);

        foreach ($permissions as $permission) {
            $response->assertSee($permission->value);
            $response->assertSee('value="'.$permission->value.'"', false);
        }
    }

    public function test_create_role_page_handles_no_permissions(): void
    {
        $response = $this->get(route($this->route));

        $response->assertStatus(200);
        $response->assertSee('No permissions available');
    }
}
