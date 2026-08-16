<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperienceViewHistory extends Model
{
    public $timestamps = false;

    protected $table = 'experience_view_history';

    protected $fillable = [
        'user_id',
        'experience_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class, 'experience_id', 'experiences_id');
    }
}
