<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ProfileableTypes;
use App\Models\Concerns\HasRole;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRole, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[Scope]
    public function searchFor($query, $keyword)
    {
        if ($keyword) {
            $query->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', '%'.$keyword.'%')
                    ->orWhere('email', 'LIKE', '%'.$keyword.'%');
            });
        }

        return $query;
    }

    #[Scope]
    public function filterByRole($query, $role)
    {
        if ($role && in_array($role, ['admin', 'editor', 'user'])) {
            $query->where('role', $role);
        }

        return $query;
    }

    public function ownChannels(): HasMany
    {
        return $this->hasMany(Channel::class, 'owner_id');
    }

    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    protected static function booted(): void
    {
        parent::booted();

        static::created(function ($user) {
            Profile::create([
                'profileable_id' => $user->id,
                'profileable_type' => ProfileableTypes::User->value,
            ]);
        });
    }

    public function isLimited()
    {
        return false; // todo: implement limitation on user for prevent spamming!
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
