<?php

namespace App\Validation\Rules;

use App\Models\Profile;
use Illuminate\Validation\Rule;

class ProfileRules
{
    public static function handle(?int $profileToIgnore = null): array
    {
        return [
            'nullable',
            'string',
            'regex:/^[a-z][a-z0-9_]{4,94}$/i',
            Rule::unique(Profile::class, 'handle')->ignore($profileToIgnore),
        ];
    }

    // todo: implement rules for these:
    // 'featured_picture'

    public static function store(): array
    {
        return [
            'handle' => self::handle(),
        ];
    }

    public static function update(int $profileToIgnore): array
    {
        return [
            'handle' => self::handle($profileToIgnore),
        ];
    }
}
