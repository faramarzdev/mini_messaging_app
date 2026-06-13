<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class StringToArrayCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return [];
        }

        return explode(',', $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        if (is_string($value)) {
            // value already stored as comma-separated string
            return $value;
        }

        return '';
    }
}
