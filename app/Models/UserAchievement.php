<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $table = 'user_achievement';
    protected $primaryKey = 'user_achievement_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'badge_id',
        'current_progress',
        'is_unlocked',
        'unlocked_date',
        'notified_at',
    ];

    protected $casts = [
        'current_progress' => 'integer',
        'is_unlocked' => 'boolean',
        'unlocked_date' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function badge(): BelongsTo
    {
        return $this->belongsTo(
            AchievementBadge::class,
            'badge_id',
            'badge_id'
        );
    }
}