<?php

namespace App\Models;

use App\Contracts\Messageable;
use App\Enums\ChannelJoinModes;
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
        'join_mode',
        'messages_count',
        'last_message_id',
        'last_activity_at',
    ];

    protected $casts = [
        'messages_count' => 'integer',
        'last_activity_at' => 'datetime',
        'type' => ChannelType::class,
        'visibility' => ChannelVisibility::class,
        'join_mode' => ChannelJoinModes::class,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    /**
     * @return HasMany filter allMembers() by their status to only invited and approved
     */
    public function members(): HasMany
    {
        return $this->allMembers()
            ->whereIn('status', [ChannelMemberStatus::Approved, ChannelMemberStatus::Invited]);
    }

    /**
     * @return HasMany all ChannelMember with no filtering
     */
    public function allMembers(): HasMany
    {
        return $this->hasMany(ChannelMember::class, 'channel_id');
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(ChannelMember::class, 'channel_id')
            ->whereIn('status', [ChannelMemberStatus::Pending]);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function profileRole(Profile $currentProfile)
    {
        return $this->members()
            ->where('profile_id', $currentProfile->id)
            ->first()?->role;
    }

    public function isProfileBlocked(Profile $profile): bool
    {
        return $this->allMembers()
            ->where('profile_id', $profile->id)
            ->whereIn('status', [ChannelMemberStatus::Blocked])
            ->exists();
    }

    public function isMember(Profile $profile): bool
    {
        return $this->members()->where('profile_id', $profile->id)->exists();
    }

    public function hasManagementPermission(Profile $currentProfile): bool
    {
        return in_array($this->profileRole($currentProfile), [ChannelRoles::Owner, ChannelRoles::Admin]);
    }

    public static function create(array $attributes = [])
    {
        throw new \RuntimeException(
            'Channels must be created through ChannelService'
        );
    }

    public function canReceiveMessageFrom(Profile $sender): bool
    {
        if ($this->type === ChannelType::Group) {
            // todo: settings for group to limit post per sender per minutes to avoid spamming/floading

            if ($this->visibility === ChannelVisibility::Public) {
                // anyone can message public group unless got blocked
                return ! $this->isProfileBlocked($sender);
            }

            return $this->members()
                ->where('profile_id', $sender->id)
                ->exists();

        }

        return $this->members()
            ->where('profile_id', $sender->id)
            ->whereIn('role', [ChannelRoles::Admin, ChannelRoles::Owner])
            ->exists();
    }
}
