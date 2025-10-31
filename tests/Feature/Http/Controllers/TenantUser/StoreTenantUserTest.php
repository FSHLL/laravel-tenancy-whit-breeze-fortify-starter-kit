<?php

namespace Tests\Feature\Http\Controllers\TenantUser;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreTenantUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private string $route = 'tenants.users.store';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create();
    }

    public function test_authenticated_user_can_create_new_tenant_user(): void
    {
        Event::fake();
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'tenant_id' => $this->tenant->id,
        ]);

        $user = User::withoutCentralApp()->where('email', $userData['email'])->first();
        $this->assertTrue(Hash::check($userData['password'], $user->password));

        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $user]));
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_store_validates_email_format(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_unique_email(): void
    {
        $existingUser = User::factory()->create();
        $userData = [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_password_confirmation(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_password_minimum_length(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_name_maximum_length(): void
    {
        $userData = [
            'name' => str_repeat('a', 256), // Over 255 characters
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_assigns_correct_tenant_id(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $user = User::withoutCentralApp()->where('email', $userData['email'])->first();
        $this->assertEquals($this->tenant->id, $user->tenant_id);
    }

    public function test_store_hashes_password(): void
    {
        $password = 'password123';
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $user = User::withoutCentralApp()->where('email', $userData['email'])->first();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotEquals($password, $user->password);
    }

    public function test_unauthenticated_user_cannot_store_user(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route($this->route, $this->tenant), $userData);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', [
            'email' => $userData['email'],
        ]);
    }

    public function test_store_handles_non_existent_tenant(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route('tenants.users.store', 999), $userData);

        $response->assertStatus(404);
    }

    public function test_store_redirects_to_user_show_page(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post(route($this->route, $this->tenant), $userData);

        $user = User::withoutCentralApp()->where('email', $userData['email'])->first();
        $response->assertRedirect(route('tenants.users.show', [$this->tenant, $user]));
    }
}
