<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Users are keyed by the UUID issued by Supabase Auth (auth.users.id).
     */
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'profile_photo',
        'bio',
        'gender',
        'birthday',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class, 'user_id', 'user_id');
    }

    public function savedExperiences(): BelongsToMany
    {
        return $this->belongsToMany(
            Experience::class,
            'favourite',
            'user_id',
            'experience_id',
            'user_id',
            'experiences_id',
        )->withPivot('saved_date');
    }
}
