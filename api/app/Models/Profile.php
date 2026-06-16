<?php

namespace App\Models;

use App\Enums\ProfileableTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LaravelIdea\Helper\App\Models\_IH_Conversation_QB;

class Profile extends Model
{
    protected $table = 'profiles';

    protected $fillable = [
        'profileable_id',
        'profileable_type',
        'handle',
    ];
    // todo: enforce profileable to have name !!
    //  using it for auto-generate the handle (yet it checks name existence)

    protected $casts = [
        'handle' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function profileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function pictures(): HasMany
    {
        return $this->hasMany(ProfilePicture::class);
    }

    public function featuredPicture(): HasOne
    {
        return $this->hasOne(ProfilePicture::class)->orderBy('created_at', 'desc')->limit(1);
    }

    public function isChannel(): bool
    {
        return $this->profileable_type === ProfileableTypes::Channel->value;
    }

    public function scopeSearchFor($query, $keyword)
    {
        if ($keyword) {
            $query->where('handle', 'LIKE', '%'.$keyword.'%');
            // todo: orderBy similarity
        }

        return $query;
    }

    /**
     * @return \LaravelIdea\Helper\App\Models\_IH_Conversation_QB
     *                                                            returns a query builder, not a relation, this breaks eager loading!
     */ // can write the relations with Conversation
    public function conversations(): _IH_Conversation_QB
    {
        return Conversation::query()->forProfile($this);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::deleted(function ($profile) {
            if ($profile->pictures) {
                $profile->pictures()->delete(); // ProfilePicture model dispatches the file removal on its own
            }
        });
    }
}
