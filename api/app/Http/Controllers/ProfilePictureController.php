<?php

namespace App\Http\Controllers;

use App\Enums\ProfileableTypes;
use App\Http\Requests\StoreProfilePictureRequest;
use App\Http\Resources\ProfilePictureResource;
use App\Models\Profile;
use App\Models\ProfilePicture;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProfilePictureController extends Controller
{
    public function store(Profile $profile, StoreProfilePictureRequest $request)
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

        //        $manager = new ImageManager(Driver::class);
        $manager = ImageManager::gd(); // Use GD driver

        $image = $manager->read($request->file('image'));
        $image->cover(640, 640); // Crop and resize to 640x640

        $encoded = (string) $image->toWebp(85); // Encode to WebP at 85% quality

        $uuid = Str::uuid();
        $date = date('Y/m/');
        $path = "{$date}{$uuid}.webp";
        dd($path);

        Storage::disk('profile_pictures')->put($path, $encoded);

        $profile = ProfilePicture::create([
            'uuid' => $uuid,
            'profile_id' => $profile->id,
            'path' => $path,
            'original_name' => $request->file('image')->getClientOriginalName(),
            'mime_type' => 'image/webp',
            'file_size' => strlen($encoded),
        ]);

        return response()->json(new ProfilePictureResource($profile), Response::HTTP_CREATED);

    }

    public function destroy(Request $request) {}
}
