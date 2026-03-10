<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private static $resource_structure = ['id', 'name', 'slug'];

    #[Test]
    public function anyone_can_list_all_tags()
    {
        Tag::factory()->count(7)->create();

        $response = $this->getJson(route('tags.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(7, 'data')
            ->assertJsonStructure([
                'data' => ['*' => self::$resource_structure],
                'meta' => ['total', 'total_page', 'current_page', 'next_page', 'prev_page', 'per_page', 'from', 'to', 'has_more'],
            ]);
    }

    #[Test]
    public function user_with_permission_can_create_tag()
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';

        $tag = [
            'name' => 'Technology',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(route('tags.store'), $tag);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure(self::$resource_structure);

        $this->assertDatabaseHas('tags', $tag);
    }

    #[Test]
    public function anyone_can_view_single_tag()
    {
        $tag = Tag::factory()->create([
            'name' => 'Specific Test',
        ]);

        $response = $this->getJson(route('tags.show', ['tag' => $tag->id]));
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::$resource_structure)
            ->assertJson([
                'name' => 'Specific Test',
            ]);
    }

    #[Test]
    public function anyone_can_view_a_tag_posts()
    {
        $tag = Tag::factory()->create();
        $posts = Post::factory(5)->create([
            'is_published' => true,
        ]);
        foreach ($posts as $post) {
            $post->tags()->attach($tag->id);
        }

        $response = $this->getJson(route('tags.posts', ['tag' => $tag->id]));
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function user_with_permission_can_update_tag()
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';

        $tag = Tag::factory()->create([
            'name' => 'Original Test',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson(route('tags.update', ['tag' => $tag->id]), [
                'name' => 'Updated Test',
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Test',
        ]);
    }

    #[Test]
    public function user_with_permission_can_delete_tag()
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';

        $tag = Tag::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson(route('tags.destroy', ['tag' => $tag->id]));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }
}
