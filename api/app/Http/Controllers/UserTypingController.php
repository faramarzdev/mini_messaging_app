<?php

namespace App\Http\Controllers;

use App\Enums\ChannelType;
use App\Events\UserTyping;
use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserTypingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => [
                'nullable',
                Rule::exists(Conversation::class, 'id'),
            ],
            'channel_id' => [
                'nullable',
                Rule::exists(Channel::class, 'id'),
            ],
        ]);
        if (
            (! $request->conversation_id && ! $request->channel_id) ||
            ($request->conversation_id && $request->channel_id)
        ) {
            return response()->json([], Response::HTTP_BAD_REQUEST);
        }

        $writer = $request->currentProfile();
        // if currentProfile changed to accept other entities, the following setRelation gets invalidated
        $writer->setRelation('profileable', $request->user());

        $messageable = null;
        if ($request->conversation_id) {
            $conversation = Conversation::find($request->conversation_id);
            if (in_array($writer->id, $conversation->connectedProfilesIds())) {
                $messageable = $conversation;
            }
        } elseif ($request->channel_id) {
            $channel = Channel::find($request->channel_id);
            if ($channel->type !== ChannelType::Channel && $channel->isMember($writer)) {
                $messageable = $channel;
            }
        }

        if (! $messageable) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        UserTyping::dispatch($writer, $messageable);

        return response()->json();
    }
}
