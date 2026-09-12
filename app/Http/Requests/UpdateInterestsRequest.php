<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:category,category_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Please select at least one interest.',
            'category_ids.min' => 'Please select at least one interest.',
        ];
    }
}
