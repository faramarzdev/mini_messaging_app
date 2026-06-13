<?php

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'is_available_on_sender',
        'messageable_type',
        'messageable_id',
        'is_available_on_receiver',
        'body',
        'type',
        'is_read',
        'reply_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_available_on_sender' => 'boolean',
        'is_available_on_receiver' => 'boolean',
        'type' => MessageType::class,
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'sender_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(MessageMedia::class);
    }

    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function replyMessage(): BelongsTo
    {
        // todo: implement checking if the replied message can be seen here (is not private)
        return $this->belongsTo(Message::class, 'reply_id');
    }

    /**
     * Authorization has to be done
     */
    public function hideForProfile(Profile $profile): bool
    {

        if ($profile->id === $this->sender_id) {
            $this->is_available_on_sender = false;
        } else {
            $this->is_available_on_receiver = false;
        }
        $result = $this->save();

        if (! $this->is_available_on_sender && ! $this->is_available_on_receiver) {
            $this->delete();
        }

        return $result;
    }

    #[Scope]
    public function availableFor(Builder $query, Profile $viewerProfile): Builder
    {
        return $query->where(function ($q) use ($viewerProfile) {
            $q->where(function ($sub) use ($viewerProfile) {
                // Viewer is sender → check sender availability
                $sub->where('sender_id', $viewerProfile->id)
                    ->where('is_available_on_sender', true);
            })->orWhere(function ($sub) use ($viewerProfile) {
                // Viewer is receiver → check receiver availability
                $sub->where('sender_id', '!=', $viewerProfile->id)
                    ->where('is_available_on_receiver', true);
            });
        });
    }
}
