<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Enums\ProfileableTypes;
use App\Http\Resources\ProfileResource;
use App\Jobs\DeleteMediaFileJob;
use App\Models\Channel;
use App\Models\Profile;
use App\Models\ProfilePicture;
use App\Models\User;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    #[Test]
    public function user_creation_would_create_the_associated_profile()
    {
        $this->assertDatabaseCount(Profile::class, 0);
        $user = User::factory()->create();
        $this->assertDatabaseHas('profiles', [
            'profileable_id' => $user->id,
            'profileable_type' => ProfileableTypes::User->value,
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
            'visibility' => ChannelVisibility::Public->value,
            'type' => ChannelType::Channel->value,
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
            'profileable_type' => ProfileableTypes::Channel->value,
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
        [$channel] = $this->createChannel();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('profile.show', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'handle' => $channel->profile->handle,
            'name' => $channel->name,
        ]);

    }

    #[Test]
    public function can_get_channel_profiles_by_set_handle()
    {
        $user = User::factory()->create();
        [$channel] = $this->createChannel();
        $channel->update(['handle' => 'channelhandle']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('profile.show', ['profile' => 'channelhandle']));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'handle' => 'channelhandle',
            'name' => $channel->name,
        ]);
    }

    #[Test]
    public function user_can_update_their_profile_handle()
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

    //    #[Test] // causing takes too long (adds around 60s to the test)
    //    public function profile_handle_creation_can_create_unique_handle_at_high_rate(): void
    //    {
    //        User::factory(5000)->create(); // user creation creates a profile
    //        $this->assertDatabaseCount(Profile::class, 5000);
    //
    //    }
    //

    // todo: implement the index for search and listings

    #[Test]
    public function user_can_upload_own_profile_picture()
    {
        $user = User::factory()->create();
        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $user->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(ProfilePicture::class, [
            'profile_id' => $user->profile->id,
            'original_name' => 'test_pp.jpg',
        ]);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));
    }

    #[Test]
    public function user_can_remove_own_profile_picture()
    {
        // upload
        $user = User::factory()->create();
        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $user->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(ProfilePicture::class, [
            'profile_id' => $user->profile->id,
            'original_name' => 'test_pp.jpg',
        ]);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));
        $uuid = $response->json('uuid');
        $path = $response->json('path');

        // remove
        $removalResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson(route('profile.picture.destroy', ['profile_picture' => $uuid]));
        $removalResponse->assertStatus(Response::HTTP_NO_CONTENT);

        Storage::disk('profile_pictures')->assertMissing($path);
        $this->assertDatabaseMissing(ProfilePicture::class, ['uuid' => $uuid]);
    }

    #[Test]
    public function deleting_a_profile_picture_dispatches_cleanup_job()
    {
        Queue::fake();
        Storage::fake('profile_pictures');

        // upload
        $user = User::factory()->create();
        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $user->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(ProfilePicture::class, [
            'profile_id' => $user->profile->id,
            'original_name' => 'test_pp.jpg',
        ]);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));
        $uuid = $response->json('uuid');
        $path = $response->json('path');

        // remove
        $removalResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson(route('profile.picture.destroy', ['profile_picture' => $uuid]));
        $removalResponse->assertStatus(Response::HTTP_NO_CONTENT);

        Queue::assertPushed(DeleteMediaFileJob::class, fn ($job) => $job->disk === 'profile_pictures' && $job->path === $path);
        $this->assertDatabaseMissing(ProfilePicture::class, ['uuid' => $uuid]);
    }

    #[Test]
    public function user_cannot_remove_others_profile_picture()
    {
        // upload
        $user = User::factory()->create();
        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $user->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(ProfilePicture::class, [
            'profile_id' => $user->profile->id,
            'original_name' => 'test_pp.jpg',
        ]);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));

        // remove
        $impersonator = User::factory()->create();
        $removalResponse = $this->actingAs($impersonator, 'sanctum')
            ->deleteJson(route('profile.picture.destroy', ['profile_picture' => $response->json('uuid')]), [
                'image' => $file,
            ]);
        $removalResponse->assertStatus(Response::HTTP_FORBIDDEN);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));
    }

    #[Test]
    public function channel_profile_picture_can_be_set_by_managements()
    {
        [$channel, $owner] = $this->createChannel();
        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $channel->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(ProfilePicture::class, [
            'profile_id' => $channel->profile->id,
        ]);
        Storage::disk('profile_pictures')->assertExists($response->json('path'));
    }

    #[Test]
    public function members_cannot_set_profile_picture_for_channel()
    {
        $member = User::factory()->create();
        [$channel] = $this->createChannel(member: $member);

        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $channel->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing(ProfilePicture::class, [
            'profile_id' => $channel->profile->id,
        ]);
    }

    #[Test]
    public function user_cannot_upload_profile_picture_when_limit_exceeded()
    {
        $user = User::factory()->create();

        ProfilePicture::factory()
            ->count(config('app.max_profile_picture_per_user'))
            ->create(['profile_id' => $user->profile->id]);

        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $user->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_NOT_ACCEPTABLE);
    }

    #[Test]
    public function cannot_upload_profile_picture_when_limit_exceeded_channel()
    {
        [$channel, $owner] = $this->createChannel();
        ProfilePicture::factory()
            ->count(config('app.max_profile_picture_per_channel'))
            ->create(['profile_id' => $channel->profile->id]);

        Storage::fake('profile_pictures');
        $file = UploadedFile::fake()->image('test_pp.jpg', 350, 350);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson(route('profile.picture.store', ['profile' => $channel->profile->handle]), [
                'image' => $file,
            ]);

        $response->assertStatus(Response::HTTP_NOT_ACCEPTABLE);
    }

    #[Test]
    public function profile_resource_throws_without_profileable_eager_loaded(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // fetch TWO rows — this is what actually triggers the flag
        $profiles = Profile::query()->whereIn('id', [$userA->profile->id, $userB->profile->id])->get();

        $this->expectException(LazyLoadingViolationException::class);
        (new ProfileResource($profiles->first()))->toArray(request());
    }
}
