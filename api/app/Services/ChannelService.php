<?php

namespace App\Services;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\MessageableType;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChannelService
{
    public function createChannel(array $validatedData, User $owner)
    {
        return DB::transaction(function () use ($validatedData, $owner) {

            // 1. Create the channel
            $channel = Channel::query()->create([
                'owner_id' => $owner->id,
                'messages_count' => 0,
                ...$validatedData,

            ]);

            // 2. Create the channel's profile
            $profileToCreate = [
                'profileable_id' => $channel->id,
                'profileable_type' => ProfileableTypes::Channel,
            ];
            if (isset($validatedData['handle'])) {
                $profileToCreate['handle'] = $validatedData['handle'];
            }
            if (isset($validatedData['featured_picture'])) {
                $profileToCreate['featured_picture'] = $validatedData['featured_picture'];
            }
            Profile::create($profileToCreate);

            ChannelMember::create([
                'channel_id' => $channel->id,
                'profile_id' => $owner->profile->id,
                'role' => ChannelRoles::Owner->value,
                'status' => ChannelMemberStatus::Approved->value,
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
            Message::where('messageable_type', MessageableType::Channel->value)
                ->where('messageable_id', $channel->id)
                ->delete();

            // delete the associated Profile
            Profile::where('profileable_id', $channel->id)->where('profileable_type', ProfileableTypes::Channel)->delete();

            // delete the associated Profile
            return $channel->delete();
        });
    }
}
