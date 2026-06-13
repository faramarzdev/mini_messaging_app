<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChannelRequest;
use App\Http\Requests\UpdateChannelRequest;
use App\Http\Resources\ChannelResource;
use App\Models\Channel;
use App\Services\ChannelService;
use Illuminate\Http\Response;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // todo: implement it
        // will use it for search
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChannelRequest $request, ChannelService $channelService)
    {
        $this->authorize('create', Channel::class);
        $validated = $request->validated();

        $user = $request->user();

        $channel = $channelService->createChannel($validated, $user);
        if ($channel) {
            return response()->json(new ChannelResource($channel), Response::HTTP_CREATED);
        }

        return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Display the specified resource.
     */
    public function show(Channel $channel)
    {
        $this->authorize('view', $channel);

        return response()->json(new ChannelResource($channel), Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChannelRequest $request, Channel $channel)
    {
        $this->authorize('update', $channel);
        $validated = $request->validated();
        if ($channel->update($validated)) {
            return response()->json(new ChannelResource($channel), Response::HTTP_OK);
        } else {
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Channel $channel, ChannelService $channelService)
    {
        $this->authorize('delete', $channel);

        if ($channelService->removeChannel($channel)) {
            return response()->json([], Response::HTTP_NO_CONTENT);
        } else {
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
