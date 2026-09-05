<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->route('field')) {
            'user_name' => ['value' => ['required', 'string', 'max:100']],
            'bio' => ['value' => ['nullable', 'string', 'max:500']],
            'gender' => ['value' => ['nullable', 'string', 'in:Male,Female,Other,Prefer not to say']],
            'birthday' => ['value' => ['nullable', 'date', 'before:today']],
            default => ['value' => ['prohibited']],
        };
    }
}
