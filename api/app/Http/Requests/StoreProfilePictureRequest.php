<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfilePictureRequest extends FormRequest
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
            'image' => [
                'image',          // image: includes jpeg, png, bmp, gif, svg, webp
                'mimes:jpeg,png,jpg',
                'max:1024',       // KB = 1MB
                // 'dimensions:min_width=300,min_height=300,max_width=4000,max_height=4000', we will crop it
            ],

        ];
    }
}
