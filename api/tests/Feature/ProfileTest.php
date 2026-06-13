<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\Profile;
use App\Models\ProfilePicture;
use App\Models\User;
use App\Services\ChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_creation_would_create_the_associated_profile()
    {
        $this->assertDatabaseCount(Profile::class, 0);
        $user = User::factory()->create();
        $this->assertDatabaseHas('profiles', [
            'profileable_id' => $user->id,
            'profileable_type' => ProfileableTypes::User,
        ]);
        $this->assertDatabaseCount(Profile::class, 1);
    }

    #[Test]
    public function channel_creation_would_create_the_associated_profile()
    {
        $user = User::factory()->create();
        $this->assertDatabaseCount(Profile::class, 1);

        $channelData = [
            'name' => 'tests',
            'visibility' => ChannelVisibility::Public,
            'type' => ChannelType::Channel,
            'can_join_by_link' => 1,
            'confirm_joined' => 0,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('channel.store'), [
                'handle' => 'tests',
                ...$channelData,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $channel = $response->json();

        $this->assertDatabaseHas(Channel::class, $channelData);

        $this->assertDatabaseHas(Profile::class, [
            'profileable_id' => $channel['id'],
            'profileable_type' => ProfileableTypes::Channel,
        ]);
        $this->assertDatabaseCount(Profile::class, 2);
    }

    #[Test]
    public function can_get_user_profiles_by_handle()
    {
        $user = User::factory()->create();

        $toFind = User::factory()->create([
            'name' => 'Test Name',
        ]);
        $toFindProfile = $toFind->profile;
        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('profile.show', ['profile' => $toFindProfile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'profileable_type' => ProfileableTypes::User->value,
            'profileable' => ['name' => 'Test Name'],
        ]);
    }

    #[Test]
    public function can_get_channel_profiles_by_handle_set_default()
    {
        $user = User::factory()->create();

        $channelsOwner = User::factory()->create();
        $channelService = new ChannelService;
        $channel = $channelService->createChannel([
            'name' => 'Channel Name',
        ], $channelsOwner);
        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('profile.show', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'profileable_type' => ProfileableTypes::Channel->value,
            'profileable' => ['name' => 'Channel Name'],
        ]);

    }

    #[Test]
    public function can_get_channel_profiles_by_set_handle()
    {
        $user = User::factory()->create();

        $channelsOwner = User::factory()->create();
        $channelService = new ChannelService;
        $channel = $channelService->createChannel([
            'name' => 'Channel Name',
            'handle' => 'channelhandle',
        ], $channelsOwner);
        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('profile.show', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'handle' => 'channelhandle',
            'profileable_type' => ProfileableTypes::Channel->value,
            'profileable' => ['name' => 'Channel Name'],
        ]);

    }

    #[Test]
    public function associated_user_can_update_their_user_profile_handle()
    {
        $user = User::factory()->create();
        $this->assertDatabaseMissing(Profile::class, [
            'handle' => 'changedhandle',
        ]);
        $this->assertDatabaseCount(Profile::class, 1);
        $profile = $user->profile;
        $response = $this->actingAs($user, 'sanctum')
            ->putJson(route('profile.update', ['profile' => $profile->id]), [
                'handle' => 'changedhandle',
            ]);
        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas(Profile::class, [
            'handle' => 'changedhandle',
        ]);
        $this->assertDatabaseCount(Profile::class, 1);
    }

    //    #[Test] // causing takes too long (adds around 60s to tests)
    //    public function profile_handle_creation_can_create_unique_handle_at_high_rate(): void
    //    {
    //        User::factory(5000)->create(); // user creation creates a profile
    //        $this->assertDatabaseCount(Profile::class, 5000);
    //
    //    }
    //

    #[Test]
    public function user_can_upload_own_profile_picture()
    {
        $user = User::factory()->create();
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $user->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);
        //            ->assertJson($postData);

        $this->assertDatabaseHas(ProfilePicture::class, [
            'profile_id' => $user->profile->id,
            'original_name' => 'test_pp.jpg',
        ]);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));

    }

    // todo: implement the index for search and listings

}
