<?php

namespace App\Http\Controllers;

use App\Enums\ProfileableTypes;
use App\Http\Requests\StoreProfilePictureRequest;
use App\Http\Resources\ProfilePictureResource;
use App\Models\Profile;
use App\Models\ProfilePicture;
use App\Services\ProfilePictureService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ProfilePictureController extends Controller
{
    public function store(Profile $profile, StoreProfilePictureRequest $request, ProfilePictureService $profilePictureService)
    {
        $this->authorize('create', [ProfilePicture::class, $profile]);
        if ($profile->profileable_type === ProfileableTypes::User->value) {
            if ($profile->pictures()->count() >= config('app.max_profile_picture_per_user')) {
                return response()->json([
                    'error' => 'user limit for profile picture exceeded!',
                ], Response::HTTP_NOT_ACCEPTABLE);
            }
        } elseif ($profile->profileable_type === ProfileableTypes::Channel->value) {
            if ($profile->pictures()->count() >= config('app.max_profile_picture_per_channel')) {
                return response()->json([
                    'error' => 'user limit for profile picture exceeded!',
                ], Response::HTTP_NOT_ACCEPTABLE);
            }
        } else {
            Log::error('Profile Type is out of range! ProfilePictureController::store() sent as: '.$profile->profileable_type);

            return response()->json([], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validated();

        $picture = $profilePictureService->store($profile, $request->file('image'));

        return response()->json(new ProfilePictureResource($picture), Response::HTTP_CREATED);

    }

    public function destroy(ProfilePicture $profilePicture)
    {
        $this->authorize('delete', [ProfilePicture::class, $profilePicture]);

        $profilePicture->delete(); // model will attempt to remove the actual file

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
