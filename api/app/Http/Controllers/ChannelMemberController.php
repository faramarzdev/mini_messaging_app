<?php

namespace App\Http\Controllers;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\ChannelVisibility;
use App\Http\Requests\ChannelMemberInviteRequest;
use App\Http\Resources\ChannelMemberCollection;
use App\Models\Channel;
use App\Models\ChannelMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChannelMemberController extends Controller
{
    public function index(Request $request, Channel $channel)
    {
        $this->authorize('view', [ChannelMember::class, $channel]);
        $members = ChannelMember::with('profile.featuredPicture')
            ->where('channel_id', $channel->id)
            ->statusFilter($request)
            ->orderBy('joined_at')->paginate(config('app.profiles_pagination_limit'));

        return response()->json(new ChannelMemberCollection($members), Response::HTTP_OK);

    }

    public function join(Request $request, Channel $channel): JsonResponse
    {
        if (! $channel->can_join_by_link) {
            return response()->json(['message' => 'Channel does not accept new member!'], Response::HTTP_FORBIDDEN);
        }

        $profile = $request->currentProfile();
        if ($channel->confirm_joined) {
            $status = ChannelMemberStatus::Pending->value;
        } else {
            if ($channel->visibility === ChannelVisibility::Public) {
                $status = ChannelMemberStatus::Approved->value;
            } else {
                $status = ChannelMemberStatus::Pending->value;
            }
        }
        if ($status) {
            $channel->members()->create([
                'profile_id' => $profile->id,
                'role' => ChannelRoles::Member->value,
                'status' => $status,
            ]);

            return response()->json([], Response::HTTP_OK);
        }

        return response()->json(['message' => 'Channel joining stat was\'t specified!'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function invite(ChannelMemberInviteRequest $request, Channel $channel): JsonResponse
    {
        $role = $channel->profileRole;
        if (! $role || ! in_array($role, [ChannelRoles::Owner->value, ChannelRoles::Admin->value])) {
            return response()->json(['message' => 'User does not have permission!'], Response::HTTP_FORBIDDEN);
        }
        $validated = $request->validated();
        $channel->members()->create([
            'profile_id' => $validated['profile_id'],
            'role' => $validated['role'],
            'status' => ChannelMemberStatus::Approved->value,
        ]);

        return response()->json([], Response::HTTP_OK);
    }

    public function leave(Request $request, Channel $channel)
    {
        $profile = $request->currentProfile();
        $inChannel = ChannelMember::where('profile_id', $profile->id)->where('channel_id', $channel->id);
        if (! $inChannel->exists()) {
            return response()->json(['message' => 'User is not a member!'], Response::HTTP_FORBIDDEN);
        }
        $inChannel = $inChannel->first();
        if ($inChannel->status === ChannelMemberStatus::Left->value) {
            return response()->json(['message' => 'Already left!'], Response::HTTP_NOT_ACCEPTABLE);
        }
        if ($inChannel->role === ChannelRoles::Owner->value) {
            return response()->json(['message' => 'Owner can not leave their channel, transfer the ownership or destroy the channel!'], Response::HTTP_FORBIDDEN);
        }
        $inChannel->status = ChannelMemberStatus::Left->value;
        $inChannel->role = ChannelRoles::Member->value;
        $inChannel->save();

        return response()->json([], Response::HTTP_OK);
    }

    public function kick(Request $request, Channel $channel)
    {
        $role = $channel->profileRole();
        if (! $role || ! in_array($role, [ChannelRoles::Owner->value, ChannelRoles::Admin->value])) {
            return response()->json(['message' => 'User does not have permission!'], Response::HTTP_FORBIDDEN);
        }
        $validated = $request->validate([
            'profile_id' => ['required', 'integer', 'exists:profiles,id'],
        ]);
        $inChannel = ChannelMember::where('profile_id', $validated['profile_id'])->where('channel_id', $channel->id);
        if (! $inChannel->exists()) {
            return response()->json(['message' => 'User is not a member!'], Response::HTTP_FORBIDDEN);
        }
        $inChannel = $inChannel->first();
        if ($inChannel->role === ChannelRoles::Owner->value) {
            return response()->json(['message' => 'Cannot kick the owner!'], Response::HTTP_FORBIDDEN);
        }
        $inChannel->status = ChannelMemberStatus::Left->value;
        $inChannel->role = ChannelRoles::Member->value;
        $inChannel->save();

        return response()->json([], Response::HTTP_OK);
    }

    public function block(Request $request, Channel $channel)
    {
        $role = $channel->profileRole();
        if (! $role || ! in_array($role, [ChannelRoles::Owner->value, ChannelRoles::Admin->value])) {
            return response()->json(['message' => 'User does not have permission!'], Response::HTTP_FORBIDDEN);
        }
        $validated = $request->validate([
            'profile_id' => ['required', 'integer', 'exists:profiles,id'],
        ]);
        $inChannel = ChannelMember::where('profile_id', $validated['profile_id'])->where('channel_id', $channel->id);
        if (! $inChannel->exists()) {
            return response()->json(['message' => 'User is not a member!'], Response::HTTP_FORBIDDEN);
        }
        $inChannel = $inChannel->first();
        if ($inChannel->role === ChannelRoles::Owner->value) {
            return response()->json(['message' => 'Cannot block the owner!'], Response::HTTP_FORBIDDEN);
        }
        $inChannel->status = ChannelMemberStatus::Blocked->value;
        $inChannel->role = ChannelRoles::Member->value;
        $inChannel->save();

        return response()->json([], Response::HTTP_OK);
    }
}
