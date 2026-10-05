<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Response;

class ConversationController extends Controller
{
    /*
     * All Conversation actions will be done automatically.
     * Only hiding should happen with user request
     */

    public function hide(Conversation $conversation, ConversationService $conversationService)
    {
        $this->authorize('hide', $conversation);
        $profile = request()->currentProfile();
        if ($conversationService->hide($conversation, $profile)) {
            return response()->json([], Response::HTTP_NO_CONTENT);
        }

        return response()->json([], Response::HTTP_EXPECTATION_FAILED);
    }
}
