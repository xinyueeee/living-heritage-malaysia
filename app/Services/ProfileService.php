<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Personal-information fields that are allowed to be edited one at a time
     * from the Personal Information page.
     *
     * @var list<string>
     */
    private const EDITABLE_FIELDS = [
        'user_name',
        'user_email',
        'bio',
        'gender',
        'birthday',
        'phone_number',
        'nationality',
    ];

    public function getProfile(string $userId): User
    {
        return User::findOrFail($userId);
    }

    public function updateField(string $userId, string $field, ?string $value): User
    {
        if (! in_array($field, self::EDITABLE_FIELDS, true)) {
            throw ValidationException::withMessages([
                'field' => ["\"{$field}\" is not an editable field."],
            ]);
        }

        $user = User::findOrFail($userId);
        $user->{$field} = $value;
        $user->save();

        return $user;
    }
}
