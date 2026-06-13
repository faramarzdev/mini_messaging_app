<?php

namespace App\Http\Requests;

use App\Enums\ChannelRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChannelMemberInviteRequest extends FormRequest
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
            'profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'role' => [
                'required',
                'string',
                Rule::in([ChannelRoles::Admin->value, ChannelRoles::Member->value]),
            ],
        ];
    }
}
