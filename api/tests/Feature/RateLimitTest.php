<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_rate_limit_blocks_after_5_attempts()
    {
        $credential = [
            'email' => 'no@email.com',
            'password' => 'wrongPassword',
        ];
        for ($i = 1; $i <= 6; $i++) {
            $response = $this->postJson(route('login'), $credential);
            if ($i <= 5) {
                $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
            }
        }
    }

    //    #[Test]
    public function comment_rate_limit_blocks_after_10_comments()
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson(route('comments.store', ['post' => $post->id]), [
                    'content' => 'Test comment number '.$i,
                ]);

            if ($i <= 10) {
                // First 10 comments should succeed
                $response->assertStatus(Response::HTTP_CREATED);
            } else {
                // 11th comment should be rate limited
                $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
            }
        }
    }
}
