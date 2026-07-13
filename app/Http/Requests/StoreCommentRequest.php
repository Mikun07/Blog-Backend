<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['nullable', 'string', 'max:120'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }
}
