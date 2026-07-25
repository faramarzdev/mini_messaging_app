<?php

namespace App\Http\Controllers;

use App\Enums\ChannelMemberStatus;
use App\Enums\MessageableType;
use App\Http\Resources\ChatCollection;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function my(Request $request): JsonResponse
    {
        $profile = $request->currentProfile();
        $perPage = config('app.per_page');

        $requested = (int) $request->input('per_page');
        if ($requested > 0 && $requested < 50) {
            $perPage = $requested;
        }

        // todo: custom filtering for channel type, searches, ...

        $profileId = $profile->id;

        $messageableConversation = MessageableType::Conversation->value;
        $messageableChannel = MessageableType::Channel->value;

        $conversations = Conversation::query()
            ->select([
                'id',
                'last_message_id',
                DB::raw("'conversation' as type"),
                DB::raw("(
            SELECT COUNT(*) FROM messages
            WHERE messages.messageable_type = '{$messageableConversation}'
              AND messages.messageable_id = conversations.id
              AND messages.deleted_at IS NULL
              AND messages.id > CASE
                WHEN conversations.lower_profile_id = {$profileId}
                THEN COALESCE(conversations.lower_profile_last_read_message_id, 0)
                ELSE COALESCE(conversations.higher_profile_last_read_message_id, 0)
              END
        ) as unread_count"),
            ])
            ->forProfile($profile)
            ->toBase();

        $channels = DB::table('channels')
            ->select([
                'channels.id',
                'channels.last_message_id',  // after migration
                DB::raw("'channel' as type"),
                DB::raw("(
            SELECT COUNT(*) FROM messages
            WHERE messages.messageable_type = '{$messageableChannel}'
              AND messages.messageable_id = channels.id
              AND messages.deleted_at IS NULL
              AND messages.id > COALESCE(channel_members.last_read_message_id, 0)
        ) as unread_count"),
            ])
            ->join('channel_members', 'channel_members.channel_id', '=', 'channels.id')
            ->where('channel_members.profile_id', $profileId)
            ->whereIn('channel_members.status', [
                ChannelMemberStatus::Approved->value,
                ChannelMemberStatus::Invited->value,
            ]);

        $paginated = DB::query()
            ->fromSub($conversations->unionAll($channels), 'chats')
            ->orderBy('last_message_id', 'desc')
            ->paginate($perPage);

        $ids = collect($paginated->items());

        $conversationIds = $ids->where('type', 'conversation')->pluck('id');
        $channelIds = $ids->where('type', 'channel')->pluck('id');

        $conversations = Conversation::whereIn('id', $conversationIds)
            ->with(['lowerProfile.featuredPicture', 'higherProfile.featuredPicture', 'lastMessage'])
            ->get()
            ->keyBy('id');

        $channels = Channel::whereIn('id', $channelIds)
            ->with(['profile.featuredPicture', 'lastMessage'])
            ->get()
            ->keyBy('id');

        $toReturn = [];
        foreach ($paginated->items() as $i => $chat) {
            if (
                $chat->type === MessageableType::Conversation->value &&
                $conversations->has($chat->id)
            ) {
                $conversation = $conversations->get($chat->id);
                $isLowerProfile = $conversation->lower_profile_id === $profileId;
                $toReturn[$i] = $conversation;
                if ($isLowerProfile) {
                    $toReturn[$i]['profile'] = $conversation->higherProfile;
                } else {
                    $toReturn[$i]['profile'] = $conversation->lowerProfile;
                }
            } elseif (
                $chat->type === MessageableType::Channel->value &&
                $channels->has($chat->id)
            ) {
                $toReturn[$i] = $channels->get($chat->id);
            }
            $toReturn[$i]['type'] = $chat->type;
            $toReturn[$i]['unread_count'] = $chat->unread_count;
        }
        $paginated->setCollection(collect($toReturn));

        return response()->json(new ChatCollection($paginated));
    }
}
