<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMessageRequest extends FormRequest
{

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
            'anchor_message_id' => [
                'nullable',
                'integer',
                // Rule::exists('messages', 'id') // it doesn't matter if id exist in that conversation or not, the service would handle it, here it's just an extra query
            ],
            'search' => ['nullable', 'string', 'min:3', 'max:255'],
            'direction' => ['nullable', 'string', 'in:up,down'],
        ];
    }
}
