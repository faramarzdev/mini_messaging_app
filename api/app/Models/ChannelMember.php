<?php

namespace App\Models;

use App\Enums\ChannelMemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMember extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelMemberFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'channel_id',
        'profile_id',
        'last_read_message_id',
        'role',
        'joined_at',
        'status',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }

    public function scopeStatusFilter($query, $request)
    {
        if ($request->has('status') && in_array($request->get('status'), array_column(ChannelMemberStatus::cases(), 'value'))) {
            $query->where('status', $request->get('status'));
        }

        return $query;
    }
}
