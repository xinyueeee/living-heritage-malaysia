<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExperienceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'location' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:category,category_id'],
            'type' => ['nullable', 'integer', 'exists:experience_type,type_id'],
            'sort' => ['nullable', 'in:newest,oldest'],
        ];
    }
}
