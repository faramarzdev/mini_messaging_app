<?php

namespace App\Models;

use App\Contracts\Messageable;
use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Models\Concerns\HasMessages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Channel extends Model implements Messageable
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use HasFactory, HasMessages, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'visibility',
        'type',
        'can_join_by_link',
        'confirm_joined',
        'messages_count',
        'last_message_id',
        'last_activity_at',
    ];

    protected $casts = [
        'can_join_by_link' => 'boolean',
        'confirm_joined' => 'boolean',
        'messages_count' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChannelMember::class, 'channel_id')
            ->whereIn('status', [ChannelMemberStatus::Approved->value, ChannelMemberStatus::Invited->value]);
    }

    public function allMembers(): HasMany
    {
        return $this->hasMany(ChannelMember::class, 'channel_id');
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(ChannelMember::class, 'channel_id')
            ->whereIn('status', [ChannelMemberStatus::Pending->value]);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function profileRole()
    {
        $currentProfile = request()->currentProfile();
        if (! $currentProfile) {
            return null;
        }

        return $this->members()
            ->where('profile_id', $currentProfile->id)
            ->first()?->role;
    }

    public static function create(array $attributes = [])
    {
        throw new \RuntimeException(
            'Channels must be created through ChannelService'
        );
    }

    public function canReceiveMessageFrom(Profile $sender): bool
    {
        if ($this->type === ChannelType::Group->value) {
            // todo: settings for group to limit post per sender per minutes to avoid spamming/floading

            if ($this->visibility === ChannelVisibility::Public->value) {
                // anyone can message public group unless got blocked
                return ! $this->allMembers()
                    ->where('profile_id', $sender->id)
                    ->where('status', ChannelMemberStatus::Blocked->value)
                    ->exists();
            }

            return $this->members()
                ->where('profile_id', $sender->id)
                ->exists();

        }

        return $this->members()
            ->where('profile_id', $sender->id)
            ->whereIn('role', [ChannelRoles::Admin->value, ChannelRoles::Owner->value])
            ->exists();
    }
}
