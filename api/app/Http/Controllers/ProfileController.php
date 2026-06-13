<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // get profiles "group by type" "order by similarity"
    }

    public function show(Profile $profile): JsonResponse
    {
        $profile->load('profileable', 'featuredPicture', 'pictures');

        return response()->json(new ProfileResource($profile), Response::HTTP_OK);
    }

    public function update(UpdateProfileRequest $request, Profile $profile): JsonResponse
    {
        $this->authorize('update', $profile);
        $validated = $request->validated();
        $profile->update($validated);

        return response()->json(new ProfileResource($profile), Response::HTTP_OK);
    }

    // destroy happens automatically when profileable has been removed (user request to delete their account or channel is being removed)
}
