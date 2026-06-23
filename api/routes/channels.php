<?php

use App\Enums\ChannelMemberStatus;
use App\Models\ChannelMember;
use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// conversation = user to user
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    if (! $user) {
        return false;
    }
    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }
    $profileId = $user->profile?->id;

    return $profileId && in_array($profileId, $conversation->connectedProfilesIds());
});

// channel and group messages
Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    if (! $user) {
        return false;
    }
    $profileId = $user->profile?->id;
    if (! $profileId) {
        return false;
    }

    return ChannelMember::where('channel_id', $channelId)
        ->where('profile_id', $profileId)
        ->where('status', ChannelMemberStatus::Approved->value)
        ->exists();
});
