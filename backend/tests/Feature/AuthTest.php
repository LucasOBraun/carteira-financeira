<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_wallet(): void
    {
        $response = $this->statefulJson('POST', '/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'test@example.com');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('wallets', ['balance' => '0.00']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'login@test.com']);
        Wallet::factory()->for($user)->create();

        $login = $this->statefulJson('POST', '/api/login', [
            'email' => 'login@test.com',
            'password' => 'password',
        ]);

        $login->assertOk();

        $this->statefulJson('GET', '/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'login@test.com');

        $this->statefulJson('POST', '/api/logout')->assertOk();
    }
}
