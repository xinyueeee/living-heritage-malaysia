<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedExperience extends Model
{
    protected $table = 'completed_experience';

    protected $primaryKey = 'completed_exp_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'experience_id',
        'completed_date',
    ];

    protected $casts = [
        'completed_date' => 'datetime',
    ];

    public function experience(): BelongsTo
    {
        return $this->belongsTo(
            Experience::class,
            'experience_id',
            'experiences_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }
}