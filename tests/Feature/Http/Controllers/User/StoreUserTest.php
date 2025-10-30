<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        tenancy()->initialize($this->tenant);

        $this->tenant->domains()->create(['domain' => $this->tenant->id]);

        $this->authenticatedUser = User::factory()->create();

        $this->route = "http://{$this->tenant->id}.localhost/users";
    }

    public function test_authenticated_user_can_create_user(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        $user = User::where('email', $userData['email'])->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        $response->assertRedirect(route('users.show', $user));
    }

    public function test_unauthenticated_user_cannot_create_user(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->post($this->route, $userData)
            ->assertRedirect(route('login'));
    }

    public function test_store_user_requires_name(): void
    {
        $userData = [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('name');
    }

    public function test_store_user_requires_email(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('email');
    }

    public function test_store_user_requires_valid_email(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('email');
    }

    public function test_store_user_requires_unique_email(): void
    {
        $existingUser = User::factory()->create();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('email');
    }

    public function test_store_user_requires_password(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('password');
    }

    public function test_store_user_requires_password_confirmation(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('password');
    }

    public function test_store_user_requires_minimum_password_length(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('password');
    }

    public function test_store_user_hashes_password(): void
    {
        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData);

        $user = User::where('email', $userData['email'])->first();
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_store_user_with_long_name(): void
    {
        $userData = [
            'name' => str_repeat('a', 256),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->authenticatedUser)
            ->post($this->route, $userData)
            ->assertSessionHasErrors('name');
    }
}
