<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Response;

class ConversationController extends Controller
{
    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

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
