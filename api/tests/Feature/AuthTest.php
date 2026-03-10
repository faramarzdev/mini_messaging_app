<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('register'), $userData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'user' => ['id', 'name'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
    }

    #[Test]
    public function user_can_login()
    {
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'john@doe.com',
            'password' => Hash::make($password),
        ]);

        $credentials = [
            'email' => 'john@doe.com',
            'password' => $password,
        ];

        $response = $this->postJson(route('login'), $credentials);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'user' => ['id', 'name'],
                'token',
            ]);
    }

    #[Test]
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token');
        $plainTextToken = $token->plainTextToken;

        // Verify token exists
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Logout
        $response = $this->withToken($plainTextToken)
            ->postJson(route('logout'));

        $response->assertStatus(Response::HTTP_OK);

        // Verify token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    #[Test]
    public function user_can_get_own_profile()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson(route('me'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ],
            ]);
    }
}
