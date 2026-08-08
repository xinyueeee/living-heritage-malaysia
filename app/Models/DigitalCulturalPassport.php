<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalCulturalPassport extends Model
{
    protected $table = 'digital_cultural_passport';

    protected $primaryKey = 'passport_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }

    public function userPassportStamps(): HasMany
    {
        return $this->hasMany(
            UserPassportStamp::class,
            'passport_id',
            'passport_id'
        );
    }
}