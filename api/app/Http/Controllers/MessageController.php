<?php

namespace App\Http\Controllers;

use App\Enums\MessageType;
use App\Enums\ProfileableTypes;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageCollection;
use App\Http\Resources\MessageResource;
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

                if ($conversation) {
                    // the conversation must appear on both side (even if removed/hide)
                    $toUpdate = [
                        'is_available_for_lower_profile' => true,
                        'is_available_for_higher_profile' => true,
                    ];

                    if (! $conversation->lower_profile_last_read_message_id) {
                        $toUpdate['lower_profile_last_read_message_id'] = $message->id;
                    }
                    if (! $conversation->higher_profile_last_read_message_id) {
                        $toUpdate['higher_profile_last_read_message_id'] = $message->id;
                    }

                    $conversation->update($toUpdate);
                }

                return $message;
            });

            return response()->json(new MessageResource($message), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //    /**
    //     * Display the specified resource.
    //     */
    //    public function show(Message $message)
    //    {
    //        $this->authorize('view', $message);
    //        return new MessageResource($message);
    //    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageRequest $request, Message $message)
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
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);
        $message->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function hide(Request $request, Message $message)
    {
        $this->authorize('hide', $message);
        $profile = $request->currentProfile();

        $message->hideForProfile($profile);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function search(Request $request, Profile $profile)
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
}
