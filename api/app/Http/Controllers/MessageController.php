<?php

namespace App\Http\Controllers;

use App\Enums\MessageableType;
use App\Enums\MessageType;
use App\Enums\ProfileableTypes;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageCollection;
use App\Http\Resources\MessageResource;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use App\Services\ConversationService;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Profile $profile)
    {
        $this->authorize('viewMessages', [Message::class, $profile]);
        $viewerProfile = $request->currentProfile();

        $messageable = MessageService::resolveMessageable($profile, $viewerProfile);
        if (! $messageable) {
            return response()->json(['message' => 'No conversation or channel found'], Response::HTTP_NOT_FOUND);
        }

        $messages = MessageService::getMessagesAroundAnchor(
            $messageable,
            $viewerProfile,
            $request->input('anchor_message_id')
        );

        return response()->json(new MessageCollection($messages), Response::HTTP_OK);
    }

    /**
     * save message.
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $this->authorize('create', Message::class);

        $validated = $request->validated();

        $profile = $request->currentProfile();
        $receiverProfile = Profile::findOrFail($validated['receiver_id']);

        // todo: implement and check if the sender is not blocked by the receiver.

        $conversation = false;

        if ($receiverProfile->profileable_type === ProfileableTypes::Channel->value) {
            $messageable = $receiverProfile->profileable;
        } else {
            $messageable = ConversationService::getOrCreateBetween($profile, $receiverProfile);
            $conversation = $messageable;
        }

        abort_unless(
            $messageable->canReceiveMessageFrom($profile),
            Response::HTTP_FORBIDDEN,
        );

        try {
            $message = DB::transaction(function () use ($profile, $messageable, $validated, $conversation) {
                // todo: prepare and add media when uploaded
                //      also the type of the message
                $type = MessageType::Text->value;
                $body = $validated['body'];
                $reply_id = $validated['reply_id'] ?? null;

                $message = $messageable->addMessage($profile, [
                    'body' => $body,
                    'type' => $type,
                    'reply_id' => $reply_id,
                ]);

                $toUpdate = [
                    'last_message_id' => $message->id,
                ];
                if ($conversation) {
                    // the conversation must appear on both side (even if it has been removed/hiden)
                    $toUpdate['is_available_for_lower_profile'] = true;
                    $toUpdate['is_available_for_higher_profile'] = true;

                    if (! $conversation->lower_profile_last_read_message_id) {
                        $toUpdate['lower_profile_last_read_message_id'] = $message->id;
                    }
                    if (! $conversation->higher_profile_last_read_message_id) {
                        $toUpdate['higher_profile_last_read_message_id'] = $message->id;
                    }

                }

                $messageable->update($toUpdate);

                return $message;
            });

            MessageSent::dispatch($message);

            return response()->json(new MessageResource($message), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error($e);

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
        $isLastMessage = $message->messageable->last_message_id === $message->id;
        if ($isLastMessage) {
            $messageable = $message->messageable();
            $lastMessageIdToSet = null;
            if ($message->messageable_type === MessageableType::Conversation->value) {
                // $profile = $request->currentProfile();
                $previousMessage = Message::where('messageable_type', $message->messageable_type)
                    ->where('messageable_id', $message->messageable_id)
                    ->where('id', '!=', $message->id)
                    /* ->where(function ($query) use ($profile) { // needs last_message_id_on_(lower and higher) then
                        $query->where(function ($query) use ($profile) {
                            $query->where('sender_id', $profile->id)
                                ->where('is_available_on_sender', true);
                        })
                            ->orWhere(function ($query) use ($profile) {
                                $query->where('sender_id', '!=',$profile->id)
                                    ->where('is_available_on_receiver', true);
                            });
                    }) */
                    ->orderBy('id', 'desc')->first();
                $lastMessageIdToSet = $previousMessage?->id;
            } elseif ($message->messageable_type === MessageableType::Channel->value) {
                $previousMessage = Message::where('messageable_type', $message->messageable_type)
                    ->where('messageable_id', $message->messageable_id)
                    ->where('id', '!=', $message->id)
                    ->orderBy('id', 'desc')->first();
                $lastMessageIdToSet = $previousMessage?->id;
            }
            $messageable->update(['last_message_id' => $lastMessageIdToSet]);
        }
        //
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

    public function search(Request $request, Profile $profile): JsonResponse
    {
        $this->authorize('viewMessages', [Message::class, $profile]);

        $viewerProfile = $request->currentProfile();

        $messageable = MessageService::resolveMessageable($profile, $viewerProfile);

        if (! $messageable) {
            return response()->json(['message' => 'No conversation or channel found'], Response::HTTP_NOT_FOUND);
        }

        $messages = MessageService::getMessagesAroundAnchor(
            $messageable,
            $viewerProfile,
            null,
            $request->input('search')
        );

        return response()->json(new MessageCollection($messages), Response::HTTP_OK);
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
