<?php

namespace App\Http\Controllers;

use App\Enums\MessageType;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Requests\IndexMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageCollection;
use App\Http\Resources\MessageResource;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexMessageRequest $request, Profile $profile)
    {
        $this->authorize('viewMessages', [Message::class, $profile]);
        $viewerProfile = $request->currentProfile();

        $messageable = MessageService::resolveMessageableForViewing($profile, $viewerProfile);
        if (! $messageable) {
            return response()->json(['message' => 'No conversation or channel found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();

        $page = MessageService::getMessagesAroundAnchor(
            messageable: $messageable,
            viewerProfile: $viewerProfile,
            anchorId: $validated['anchor_message_id'] ?? null,
            direction: $validated['direction'] ?? 'down',
            search: $validated['search'] ?? null,
        );

        return response()->json(new MessageCollection($page), Response::HTTP_OK);
    }

    public function store(StoreMessageRequest $request, MessageService $messageService): JsonResponse
    {
        $this->authorize('create', Message::class);

        $validated = $request->validated();

        $senderProfile = $request->currentProfile();
        $receiverProfile = Profile::where('handle', $validated['receiver_handle'])->firstOrFail();

        // resolveMessageableForSending creates a conversation (if not exists), and if creating message fails, it would still show up on receiver's chat list
        $messageable = $messageService->resolveMessageableForSending($senderProfile, $receiverProfile);
        abort_if(! $messageable, Response::HTTP_NOT_FOUND);

        // todo: implement and check if the sender is not blocked by the receiver.
        abort_unless(
            $messageable->canReceiveMessageFrom($senderProfile),
            Response::HTTP_FORBIDDEN,
        );

        $message = $messageService->send($senderProfile, $messageable, $validated);

        if ($message) {
            // the message is already sent so the event handle shouldn't change the return
            try {
                MessageSent::dispatch($message);
            } catch (\Throwable $e) {
                Log::error($e);
            }

            return response()->json(new MessageResource($message), Response::HTTP_CREATED);
        } else {
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageRequest $request, Message $message): JsonResponse
    {
        $this->authorize('update', $message);

        $validated = $request->validated();

        $updated = $message->update([
            'body' => $validated['body'],
            // todo: MessageType Implementation || also on UpdateMessageRequest
        ]);

        if ($updated) {
            return response()->json(new MessageResource($message), Response::HTTP_OK);
        } else {
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * soft delete the specified resource, only when the receiver didn't see the message
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        $this->authorize('delete', $message);
        // this deletes the resource for both side based on some business logic, check authorized

        $isLastMessage = $message->messageable->last_message_id === $message->id;
        if ($isLastMessage) {
            $previousMessage = Message::where('messageable_type', $message->messageable_type)
                ->where('messageable_id', $message->messageable_id)
                ->where('id', '!=', $message->id)
                ->orderBy('id', 'desc')->first();
            $lastMessageIdToSet = null;
            $messageable = $message->messageable;
            $lastActivityAtToSet = $messageable->created_at; // can't be null
            if ($previousMessage) {
                $lastMessageIdToSet = $previousMessage->id;
                $lastActivityAtToSet = $previousMessage->created_at;
            }

            $messageable->update([
                'last_message_id' => $lastMessageIdToSet,
                'last_activity_at' => $lastActivityAtToSet,
            ]);
        }
        $message->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function hide(Request $request, Message $message): JsonResponse
    {
        $this->authorize('hide', $message);
        $profile = $request->currentProfile();

        $message->hideForProfile($profile);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * real-time event; no impact/change on any models
     */
    public function markAsRead(Request $request, Message $message): JsonResponse
    {
        // $this->authorize('read', $message);
        // as we need to check the messageable type and update the message and messageable data , we authorize the query here to avoid doubling queries and logic

        $readerProfile = $request->currentProfile();
        if ($message->messageable instanceof Conversation) {
            $conversation = $message->messageable;
            if (! in_array($readerProfile->id, $conversation->connectedProfilesIds())) {
                return response()->json([], Response::HTTP_FORBIDDEN);
            }

            $isLowerProfile = $readerProfile->id === $conversation->lower_profile_id;
            if ($isLowerProfile && $conversation->lower_profile_last_read_message_id < $message->id) {
                $conversation->update(['lower_profile_last_read_message_id' => $message->id]);

            } elseif (! $isLowerProfile && $conversation->higher_profile_last_read_message_id < $message->id) {
                $conversation->update(['higher_profile_last_read_message_id' => $message->id]);
            }

            $message->update(['is_read' => true]);
            MessageRead::dispatch($message, $readerProfile);

        } elseif ($message->messageable instanceof Channel) {
            $member = ChannelMember::where('channel_id', $message->messageable->id)
                ->where('profile_id', $readerProfile->id)
                ->first();
            if ($member && $member->last_read_message_id < $message->id) {
                $member->update(['last_read_message_id' => $message->id]);
                MessageRead::dispatch($message, $readerProfile);
            }
        }

        return response()->json([], Response::HTTP_OK);
    }
}
