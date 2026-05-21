<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Front Desk User',
            'email' => 'frontdesk@example.com',
            'password' => 'password123',
            'role' => 'front_desk',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'frontdesk@example.com',
            'role' => 'front_desk',
        ]);
    }

    public function test_register_returns_validation_errors(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_active_user_can_login_and_receive_token(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email', 'role'], 'token']);
    }

    public function test_login_rejects_invalid_or_inactive_users(): void
    {
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'role' => 'front_desk',
            'is_active' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = $this->user();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);

        $token = $user->createToken('test-token');

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out');
    }

    private function user(string $role = 'admin'): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . '@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
