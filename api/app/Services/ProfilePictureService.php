<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfilePicture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ProfilePictureService
{
    public function store(Profile $profile, UploadedFile $file)
    {
        $defaultWidthAndHeight = 640;
        $manager = ImageManager::usingDriver(Driver::class);
        // todo: feature: resize and crop from a specific positions

        $image = $manager->decodePath($file);
        $imageWidth = $image->width();
        $imageHeight = $image->height();

        // make the smallest dimension to meet the default size (if is smaller)
        $imageSmallestDimension = min($imageWidth, $imageHeight);
        if ($imageSmallestDimension < $defaultWidthAndHeight) {
            $multiplier = $defaultWidthAndHeight / $imageSmallestDimension;
            $image->resize($imageWidth * $multiplier, $imageHeight * $multiplier);
        }
        // crop it for the other dimension
        $image->cover(640, 640);

        $encoded = (string) $image->encodeUsingFormat(Format::WEBP, quality: 80);

        $uuid = Str::uuid();
        $date = date('Y/m/');
        $path = "{$date}{$uuid}.webp";

        Storage::disk('profile_pictures')->put($path, $encoded);

        return ProfilePicture::create([
            'uuid' => $uuid,
            'profile_id' => $profile->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => 'image/webp',
            'size' => strlen($encoded),
        ]);
    }
}
