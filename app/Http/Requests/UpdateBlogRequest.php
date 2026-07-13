<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends FormRequest
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
            'id' => ['sometimes', 'integer', 'exists:blogs,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image_url' => ['nullable', 'url', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_name' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
        ];
    }
}
