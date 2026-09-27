<?php

namespace App\Models;

use App\Contracts\Messageable;
use App\Models\Concerns\HasMessages;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class Conversation extends Model implements Messageable
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory, HasMessages;

    protected $fillable = [
        'lower_profile_id',
        'is_available_for_lower_profile',
        'higher_profile_id',
        'is_available_for_higher_profile',

        'last_message_id',
        'last_activity_at',

        'lower_profile_last_read_message_id',
        'higher_profile_last_read_message_id',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function lowerProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'lower_profile_id');
    }

    public function higherProfile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'higher_profile_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable')
            ->orderBy('created_at', 'desc');
    }

    public function connectedProfilesIds(): array
    {
        return [
            $this->lower_profile_id,
            $this->higher_profile_id,
        ];
    }

    public function getOtherProfileId(int $thisProfileId): int
    {
        if ($thisProfileId === $this->lower_profile_id) {
            return $this->higher_profile_id;
        }

        return $this->lower_profile_id;
    }

    #[Scope]
    public function forProfile(Builder $query, $profile): Builder
    {
        $profileId = $profile->id;

        return $query->where(function ($q) use ($profileId) {
            $q->where('lower_profile_id', $profileId)
                ->where('is_available_for_lower_profile', true);
        })
            ->orWhere(function ($q) use ($profileId) {
                $q->where('higher_profile_id', $profileId)
                    ->where('is_available_for_higher_profile', true);
            });
    }

    public static function normalizeProfiles(int $a, int $b): array
    {
        return $a < $b
            ? [$a, $b]
            : [$b, $a];
    }

    public function canReceiveMessageFrom(Profile $sender): bool
    {
        return in_array($sender->id, $this->connectedProfilesIds());
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($conversation) {
            $conversation->last_activity_at ??= Carbon::now();
        });
    }
}
