<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_rate_limit_blocks_after_10_attempts()
    {
        $credential = [
            'email' => 'not@a.user',
            'password' => 'password',
        ];
        for ($i = 1; $i <= 11; $i++) {
            $response = $this->postJson(route('login'), $credential);
            if ($i <= 10) {
                $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
            }
        }
    }
}
