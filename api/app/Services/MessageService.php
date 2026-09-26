<?php

namespace App\Services;

use App\DataTransferObjects\MessagePage;
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
        string $direction = 'down',
        ?string $search = null,
    ): MessagePage {
        if ($direction === 'up') {
            $beforeCount = config('app.messages_count_after_anchor_for_pagination', 30);
            $afterCount = config('app.messages_count_before_anchor_for_pagination', 10);
        } else {
            $beforeCount = config('app.messages_count_before_anchor_for_pagination', 10);
            $afterCount = config('app.messages_count_after_anchor_for_pagination', 30);
        }

        $totalCount = $beforeCount + $afterCount;

        $anchorId ??= self::getLastReadMessageId($messageable, $viewerProfile);

        $query = Message::query()
            ->with(['sender.profileable', 'sender.featuredPicture'])
            ->where('messageable_type', $messageable->getMorphClass())
            ->where('messageable_id', $messageable->id)
            ->availableFor($viewerProfile);

        if (!empty($search)) {
            $query->where('body', 'like', '%'.$search.'%');
        }

        if ($anchorId) {
            $before = (clone $query)
                ->where('id', '<', $anchorId)
                ->orderBy('id', 'desc')
                ->limit($beforeCount + 1)
                ->get()
                ->reverse()
                ->values();

            $hasMoreBefore = $before->count() > $beforeCount;
            if ($hasMoreBefore) {
                // extra/farthest row is now first (ascending) — drop it, keep the ones closest to anchor
                $before = $before->slice(1)->values();
            }

            $anchor = (clone $query)->where('id', $anchorId)->first();

            $after = (clone $query)
                ->where('id', '>', $anchorId)
                ->orderBy('id', 'asc')
                ->limit($afterCount + 1)
                ->get();

            $hasMoreAfter = $after->count() > $afterCount;
            if ($hasMoreAfter) {
                // extra/farthest row is last here — drop it
                $after = $after->slice(0, $afterCount)->values();
            }

            // before + anchor + after is now fully ascending (oldest -> newest)
            $messages = $before->concat($anchor ? [$anchor] : [])->concat($after);

            return new MessagePage(
                messages: $messages->reverse()->values(), // -> newest-first; values() is what was missing
                hasMoreBefore: $hasMoreBefore,
                hasMoreAfter: $hasMoreAfter,
            );
        }

        $recent = $query->orderBy('id', 'desc')->limit($totalCount + 1)->get();
        $hasMoreBefore = $recent->count() > $totalCount;
        if ($hasMoreBefore) {
            $recent = $recent->slice(0, $totalCount); // offset 0 keeps keys sequential — no values() needed here
        }

        return new MessagePage(
            messages: $recent, // already DESC/newest-first as fetched — no reverse needed at all
            hasMoreBefore: $hasMoreBefore,
            hasMoreAfter: false,
        );
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
