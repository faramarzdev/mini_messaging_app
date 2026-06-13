<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    //    #[Test]
    public function users_cant_get_users_list()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    //    #[Test]
    public function admin_can_get_users_list()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));
        $response->assertStatus(Response::HTTP_OK);
    }
}
