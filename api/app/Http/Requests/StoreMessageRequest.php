<?php

namespace App\Http\Requests;

use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
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
            'receiver_id' => [
                'required',
                Rule::exists(Profile::class, 'id'),
            ],
            'body' => ['required', 'string', 'min:3', 'max:2047'],
            'medias' => ['sometimes', 'array'],
            'medias.*' => [
                'sometimes',
                Rule::exists(MessageMedia::class, 'uuid'),
            ],
            'reply_id' => [
                'nullable',
                Rule::exists(Message::class),
            ],
        ];
    }
}
