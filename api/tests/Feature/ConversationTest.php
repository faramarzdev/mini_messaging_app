<?php

namespace Tests\Feature;

use App\Enums\ProfileableTypes;
use App\Models\Conversation;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    //    protected function setUp(): void
    //    {
    //        parent::setUp();
    //        //        $this->migrateDatabases();
    //    }

    #[Test]
    public function user_can_get_their_conversations()
    {
        $user = User::factory()->create();
        // user creation must make the profile

        $profile = $user->profile;

        Conversation::factory(3)->create([
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
        ]);
        Conversation::factory(1)->create([
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => false,
        ]);
        Conversation::factory(2)->create([
            'higher_profile_id' => $profile->id,
            'is_available_for_higher_profile' => true,
        ]);

        Conversation::factory(4)->create();

        $this->assertDatabaseCount(Conversation::class, 10);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('conversations.my'));
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    #[Test]
    public function user_conversations_has_correct_pagination()
    {
        $user = User::factory()->create();
        // user creation must make the profile
        $profile = Profile::where('profileable_type', ProfileableTypes::User->value)
            ->where('profileable_id', $user->id)->first();

        Conversation::factory(60)->create([
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
        ]);

        Conversation::factory(40)->create();

        $this->assertDatabaseCount(Conversation::class, 100);

        $response = $this->actingAs($user)
            ->getJson(route('conversations.my'));
        $response->assertOk();
        $response->assertJsonCount(50, 'data');
        assertequals($response->json('meta')['total'], 60);
    }

    #[Test]
    public function user_cannot_get_others_conversations()
    {
        $user = User::factory()->create();
        // user creation must make the profile

        $profile = Profile::where('profileable_type', ProfileableTypes::User->value)
            ->where('profileable_id', $user->id)->first();

        Conversation::factory(1)->create([
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
        ]);

        Conversation::factory(9)->create();

        $this->assertDatabaseCount(Conversation::class, 10);

        $response = $this->actingAs($user)
            ->getJson(route('conversations.my'));
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function user_can_hide_their_conversations()
    {
        $lowerUser = User::factory()->create();
        $lowerProfile = $lowerUser->profile;

        $user = User::factory()->create();
        $profile = $user->profile;

        $conversationAsLower = Conversation::factory()->create([
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
        $conversationAsHigher = Conversation::factory()->create([
            'lower_profile_id' => $lowerProfile->id,
            'is_available_for_lower_profile' => true,
            'higher_profile_id' => $profile->id,
            'is_available_for_higher_profile' => true,
        ]);

        Conversation::factory(4)->create();
        $this->assertDatabaseCount(Conversation::class, 6);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsLower->id,
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
        $this->actingAs($user, 'sanctum')
            ->postJson(route('conversations.hide', ['conversation' => $conversationAsLower->id]));
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsLower->id,
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => false,
            'is_available_for_higher_profile' => true,
        ]);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsHigher->id,
            'higher_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
        $this->actingAs($user, 'sanctum')
            ->postJson(route('conversations.hide', ['conversation' => $conversationAsHigher->id]));
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsHigher->id,
            'higher_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => false,
        ]);

    }

    #[Test]
    public function user_cannot_hide_others_conversations()
    {
        $lowerUser = User::factory()->create();
        $lowerProfile = $lowerUser->profile;

        $user = User::factory()->create();
        $profile = $user->profile;

        $conversationToCreate = [
            'lower_profile_id' => $lowerProfile->id,
            'is_available_for_lower_profile' => true,
            'higher_profile_id' => $profile->id,
            'is_available_for_higher_profile' => true,
        ];
        $conversation = Conversation::factory()->create($conversationToCreate);

        $this->assertDatabaseHas(Conversation::class, $conversationToCreate);

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson(route('conversations.hide', ['conversation' => $conversation->id]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas(Conversation::class, $conversationToCreate);
    }

    #[Test]
    public function not_logged_in_get_error()
    {
        Conversation::factory(50)->create();
        $this->assertDatabaseCount(Conversation::class, 50);
        $this->getJson(route('conversations.my'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
