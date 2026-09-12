<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ExperienceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The Discover page is reachable from bookmarked/shared links with no
     * reliable "previous page" in the session (e.g. a stale background poll
     * such as /notifications/count, or no referer at all), so redirecting
     * "back" on failure is not safe here — it must always return to the
     * Discover page itself with the invalid filters dropped.
     */
    protected function failedValidation(Validator $validator): void
    {
        $invalidFields = array_keys($validator->errors()->toArray());

        throw new HttpResponseException(
            redirect()->route('experiences.index', $this->except($invalidFields))
                ->withErrors($validator)
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'location' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'required_with:category', 'integer', 'exists:experience_type,type_id'],
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
            'type.required_with' => 'Select an experience type before choosing a category.',
        ];
    }
}
