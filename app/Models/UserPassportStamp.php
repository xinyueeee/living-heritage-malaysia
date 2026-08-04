<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPassportStamp extends Model
{
    protected $table = 'user_passport_stamp';

    protected $primaryKey = 'user_stamp_id';

    public $timestamps = false;

    protected $fillable = [
        'passport_id',
        'stamp_id',
        'collected_date',
    ];

    protected function casts(): array
    {
        return [
            'collected_date' => 'datetime',
        ];
    }

    public function stamp(): BelongsTo
    {
        return $this->belongsTo(
            PassportStamp::class,
            'stamp_id',
            'stamp_id'
        );
    }

    public function passport(): BelongsTo
    {
        return $this->belongsTo(
            DigitalCulturalPassport::class,
            'passport_id',
            'passport_id'
        );
    }
}