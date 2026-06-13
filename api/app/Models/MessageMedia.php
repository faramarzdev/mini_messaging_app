<?php

namespace App\Models;

use App\Jobs\DeleteMediaFileJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MessageMedia extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($media) {
            $media->uuid = Str::uuid();
        });

        static::deleted(function ($media) {
            dispatch(new DeleteMediaFileJob($media->disk, $media->path));
        });

    }
}
