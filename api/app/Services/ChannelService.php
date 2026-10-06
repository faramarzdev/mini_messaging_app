<?php

namespace App\Services;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\MessageableType;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChannelService
{
    public function createChannel(array $validatedData, User $owner)
    {
        return DB::transaction(function () use ($validatedData, $owner) {

            $now = Carbon::now();

            $allowedChannelFields = [
                'name',
                'description',
                'visibility',
                'type',
                'join_mode',
            ];
            $channelData = Arr::only($validatedData, $allowedChannelFields);

            $allowedProfileFields = [
                'handle',
                'featured_picture',
            ];
            $profileData = Arr::only($validatedData, $allowedProfileFields);

            // 1. Create the channel
            $channel = Channel::query()->create([
                ...$channelData,
                'owner_id' => $owner->id,
                'messages_count' => 0,
                'last_activity_at' => $now,
            ]);

            // 2. Create the channel's profile
            $profileToCreate = [
                ...$profileData,
                'profileable_id' => $channel->id,
                'profileable_type' => ProfileableTypes::Channel,
            ];

            Profile::create($profileToCreate);

            ChannelMember::create([
                'channel_id' => $channel->id,
                'profile_id' => $owner->profile->id,
                'role' => ChannelRoles::Owner,
                'status' => ChannelMemberStatus::Approved,
            ]);

            return $channel;
        });
    }

    public function removeChannel(Channel $channel)
    {
        return DB::transaction(function () use ($channel) {
            // todo:
            //  delete the associated Media , message medias and also profile pictures
            //  delete User_Conversations (if created)

            // delete the associated Messages and Conversations
            // todo: think might give the channel's messages a grace period (maybe if owner account is deleted automatically for being offline long enough)
            //            $channelProfileId = $channel->profile->id;
            //            $conversations = Conversation::where('lower_profile_id', $channelProfileId)
            //                ->orWhere('higher_profile_id', $channelProfileId);
            //            $conversations_ids = $conversations->pluck('id')->toArray();
            //            Message::whereIn('conversation_id', $conversations_ids)->delete();
            //            $conversations->delete();
            Message::where('messageable_type', MessageableType::Channel)
                ->where('messageable_id', $channel->id)
                ->delete();

            // delete the associated Profile
            Profile::where('profileable_id', $channel->id)->where('profileable_type', ProfileableTypes::Channel)->delete();

            // delete the associated Profile
            return $channel->delete();
        });
    }
}
