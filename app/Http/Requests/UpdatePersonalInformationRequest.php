<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalInformationRequest extends FormRequest
{
    private const NAME_MAX_LENGTH = 20;

    private const BIO_MAX_WORDS = 100;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->route('field')) {
            'user_name' => ['value' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH]],
            'bio' => ['value' => ['nullable', 'string', 'max:500', $this->maxWords(self::BIO_MAX_WORDS)]],
            'gender' => ['value' => ['nullable', 'string', 'in:Male,Female,Other,Prefer not to say']],
            'birthday' => ['value' => ['nullable', 'date', 'before:today']],
            default => ['value' => ['prohibited']],
        };
    }

    private function maxWords(int $max): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($max) {
            if (is_string($value) && str_word_count($value) > $max) {
                $fail("The {$attribute} must not exceed {$max} words.");
            }
        };
    }

    public function messages(): array
    {
        return [
            'value.max' => match ($this->route('field')) {
                'user_name' => 'Name must not exceed '.self::NAME_MAX_LENGTH.' characters.',
                'bio' => 'Bio must not exceed 500 characters.',
                default => 'This value is too long.',
            },
        ];
    }
}
