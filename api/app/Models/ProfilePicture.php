<?php

namespace App\Models;

use App\Jobs\DeleteMediaFileJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProfilePicture extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'profile_id',
        'path',
        'original_name',
        'mime_type',
        'size',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($picture) {
            $picture->uuid = Str::uuid();

            // Validate it's an image
            if (! str_starts_with($picture->mime_type, 'image/')) {
                throw new \InvalidArgumentException('Profile pictures must be images');
            }
        });

        static::deleted(function ($picture) {
            dispatch(new DeleteMediaFileJob('profile_pictures', $picture->path));
        });
    }
}
