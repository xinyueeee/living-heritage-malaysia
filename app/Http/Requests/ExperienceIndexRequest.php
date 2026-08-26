<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['nullable', 'integer', 'exists:experience_type,type_id'],
            'category' => [
                'nullable',
                'integer',
                Rule::exists('category', 'category_id')
                    ->where('type_id', $this->integer('type')),
            ],
            'sort' => ['nullable', 'in:newest,oldest'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category.exists' => 'Choose a category that belongs to the selected experience type.',
        ];
    }
}
