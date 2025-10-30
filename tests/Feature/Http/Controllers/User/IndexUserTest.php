<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IndexUserTest extends TestCase
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

        $this->route = "http://{$this->tenant->id}.localhost/users";

        $this->authenticatedUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_authenticated_user_can_access_users_index(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertSee(route('users.create'))
            ->assertSee('View', false)
            ->assertSee('Edit', false)
            ->assertSee('Delete', false)
            ->assertViewHas('users');
    }

    public function test_unauthenticated_user_cannot_access_users_index(): void
    {
        $this->get($this->route)
            ->assertRedirect(route('login'));
    }

    public function test_users_index_displays_paginated_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->authenticatedUser)
            ->get($this->route);

        $response->assertOk()
            ->assertViewIs('users.index')
            ->assertViewHas('users');

        $viewUsers = $response->viewData('users');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $viewUsers);
    }

    public function test_users_index_displays_user_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Verified');
    }

    public function test_users_index_displays_unverified_email_status(): void
    {
        $user = User::factory()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('Unverified');
    }

    public function test_users_index_displays_two_factor_status(): void
    {
        // User with 2FA enabled
        User::factory()->create([
            'name' => '2FA User',
            'two_factor_secret' => 'some-secret',
        ]);

        // User with 2FA disabled
        User::factory()->create([
            'name' => 'No 2FA User',
            'two_factor_secret' => null,
        ]);

        $response = $this->actingAs($this->authenticatedUser)
            ->get($this->route);

        $response->assertOk()
            ->assertSee('2FA User')
            ->assertSee('No 2FA User');

        // Check for 2FA status indicators
        $content = $response->getContent();
        $this->assertStringContainsString('Enabled', $content);
        $this->assertStringContainsString('Disabled', $content);
    }

    public function test_users_index_displays_empty_state_when_no_users(): void
    {
        // Delete all users except the authenticated one
        User::whereNot('id', $this->authenticatedUser->id)->delete();

        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('No users')
            ->assertSee('Get started by creating a new user.')
            ->assertSee('Create your first user');
    }
}
