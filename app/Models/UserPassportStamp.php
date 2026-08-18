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
        'completed_exp_id',
        'collected_date',
        'page_number',
        'position_x',
        'position_y',
        'rotation',
        'scale',
        'z_index',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'collected_date' => 'datetime',
            'page_number' => 'integer',
            'position_x' => 'float',
            'position_y' => 'float',
            'rotation' => 'float',
            'scale' => 'float',
            'z_index' => 'integer',
            'notified_at' => 'datetime',
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

    public function completedExperience(): BelongsTo
    {
        return $this->belongsTo(
            CompletedExperience::class,
            'completed_exp_id',
            'completed_exp_id'
        );
    }
}