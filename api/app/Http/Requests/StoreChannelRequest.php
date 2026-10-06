<?php

namespace App\Http\Requests;

use App\Enums\ChannelJoinModes;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Validation\Rules\ProfileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:5', 'max:95'],
            'description' => ['nullable', 'string', 'min:5', 'max:255'],
            'visibility' => ['required', 'string', new Enum(ChannelVisibility::class)],
            'type' => ['required', 'string', new Enum(ChannelType::class)],
            'join_mode' => ['required', 'string', new Enum(ChannelJoinModes::class)],

            ...ProfileRules::store(),
        ];
    }
}
