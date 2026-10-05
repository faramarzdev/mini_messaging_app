<?php

namespace App\Services;

use App\DataTransferObjects\MessagePage;
use App\Enums\MessageableType;
use App\Enums\MessageType;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

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

    public function send(Profile $sender, Conversation|Channel $messageable, array $validatedData): Message
    {
        $message = DB::transaction(function () use ($sender, $messageable, $validatedData) {
            // todo: prepare and add media when uploaded
            //      also the type of the message
            $type = MessageType::Text->value;
            $body = $validatedData['body'];
            $replyId = $validatedData['reply_id'] ?? null;

            $message = $messageable->addMessage($sender, [
                'body' => $body,
                'type' => $type,
                'reply_id' => $replyId,
            ]);

            $toUpdate = [
                'last_message_id' => $message->id,
                'last_activity_at' => $message->created_at,
            ];
            if ($messageable instanceof Conversation) {
                // the conversation must appear on both side (even if it has been removed/hiden)
                $toUpdate['is_available_for_lower_profile'] = true;
                $toUpdate['is_available_for_higher_profile'] = true;

                // update sender's anchor
                $isSenderLower = $sender->id === $messageable->lower_profile_id;
                if ($isSenderLower) {
                    $toUpdate['lower_profile_last_read_message_id'] = $message->id;
                } else {
                    $toUpdate['higher_profile_last_read_message_id'] = $message->id;
                }
            }

            $messageable->update($toUpdate);

            return $message;
        });

        $message->loadMissing(['sender.profileable', 'sender.featuredPicture', 'messageable']);

        return $message;
    }

    /***
     * @param  Profile  $sender
     * @param  Profile  $receiver
     * @return Conversation|Channel|null
     * It creates conversation if not exists. No membership checks on channel.
     */
    public function resolveMessageableForSending(Profile $sender, Profile $receiver): Conversation|Channel|null
    {
        if ($receiver->profileable_type === ProfileableTypes::Channel->value) {
            $messageable = $receiver->profileable;
        } else {
            $messageable = ConversationService::getOrCreateBetween($sender, $receiver);
        }

        return $messageable;
    }

    /***
     * @param  Profile  $profile
     * @param  Profile  $viewerProfile
     * @return Conversation|Channel|null
     * Returns null if conversation not exists. also Channel (channel and group type) would checks for membership
     */
    public static function resolveMessageableForViewing(Profile $profile, Profile $viewerProfile): Conversation|Channel|null
    {
        // just resolving the messageable, authorization is already checked at MessagePolicy::viewMessages
        if ($profile->profileable_type === ProfileableTypes::User->value) {
            return ConversationService::getBetween($viewerProfile, $profile);
        }

        if ($profile->profileable_type === ProfileableTypes::Channel->value) {
            return $profile->profileable;
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

        if (! empty($search)) {
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
