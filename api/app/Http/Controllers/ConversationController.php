<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationCollection;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Response;

class ConversationController extends Controller
{
    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

    }

    /**
     * user conversations
     */
    public function my()
    {
        $perPage = config('app.per_page');
        $user = request()->user();
        $userConversations = Conversation::query()->forProfile($user->profile)->orderBy('updated_at', 'desc')->paginate($perPage);

        return response()->json(new ConversationCollection($userConversations), Response::HTTP_OK);
    }

    public function hide(Conversation $conversation, ConversationService $conversationService)
    {
        $this->authorize('hide', $conversation);
        $profile = request()->currentProfile();
        if ($conversationService->hide($conversation, $profile)) {
            return response()->json([], Response::HTTP_NO_CONTENT);
        }

        return response()->json([], Response::HTTP_EXPECTATION_FAILED);
    }

    /*
     * All Conversation actions will be done automatically.
     */

}
