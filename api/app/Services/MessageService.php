<?php

namespace App\Services;

use App\Enums\MessageableType;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Collection;

class MessageService
{
    /**
     * Used in removing conversation: user removes a conversation -> all conversation's messages must get hidden for that profile
     *
     * already must be checked/authorized as the profile belongs to the conversation
     *    the profile must be either the sender or receiver
     */
    public static function hideAllMessagesForProfileConversation(Conversation $conversation, Profile $profile): void
    {

        $messagesAsSender = Message::where('messageable_type', MessageableType::Conversation->value)
            ->where('messageable_id', $conversation->id)
            ->where('sender_id', $profile->id);
        $messagesAsSender->update([
            'is_available_on_sender' => false,
        ]);

        $messagesAsReceiver = Message::where('messageable_type', MessageableType::Conversation->value)
            ->where('messageable_id', $conversation->id)
            ->where('sender_id', '!=', $profile->id);
        $messagesAsReceiver->update([
            'is_available_on_receiver' => false,
        ]);

        // soft delete if hidden on both side
        $messagesAsSender->where('is_available_on_receiver', false)->delete();
        $messagesAsReceiver->where('is_available_on_sender', false)->delete();
    }

    public static function resolveMessageable(Profile $profile, Profile $viewerProfile): Conversation|Channel|null
    {
        if ($profile->profileable_type === ProfileableTypes::User->value) {
            return ConversationService::getBetween($viewerProfile, $profile);
        }

        if ($profile->profileable_type === ProfileableTypes::Channel->value) {
            $isMember = ChannelMember::where('channel_id', $profile->profileable_id)
                ->where('profile_id', $viewerProfile->id)
                ->exists();

            return $isMember ? $profile->profileable : null;
        }

        return null;
    }

    public static function getMessagesAroundAnchor(
        Conversation|Channel $messageable,
        Profile $viewerProfile,
        ?int $anchorId = null,
        string $search = ''
    ): Collection {
        $anchorId ??= self::getLastReadMessageId($messageable, $viewerProfile);
        $query = Message::query()
            ->where('messageable_type', $messageable->getMorphClass()) // get_class($messageable)
            ->where('messageable_id', $messageable->id)
            ->availableFor($viewerProfile);

        if (! empty($search)) {
            $query->where('body', 'like', '%'.$search.'%');
        }

        if ($anchorId) {
            $before = (clone $query)->where('id', '<', $anchorId)
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get()
                ->reverse();

            $anchor = (clone $query)->where('id', $anchorId)->first();

            $after = (clone $query)->where('id', '>', $anchorId)
                ->orderBy('id', 'asc')
                ->limit(20)
                ->get();

            return $before->concat($anchor ? [$anchor] : [])->concat($after);
        }

        return $query->orderBy('id', 'desc')->limit(30)->get()->reverse();
    }

    private static function getLastReadMessageId(Conversation|Channel $messageable, Profile $viewerProfile): ?int
    {
        if ($messageable instanceof Conversation) {
            $isLower = $viewerProfile->id < $messageable->getOtherProfileId($viewerProfile->id);

            return $isLower
                ? $messageable->lower_profile_last_read_message_id
                : $messageable->higher_profile_last_read_message_id;
        }

        // Channel
        return ChannelMember::where('channel_id', $messageable->id)
            ->where('profile_id', $viewerProfile->id)
            ->value('last_read_message_id');
    }
}
