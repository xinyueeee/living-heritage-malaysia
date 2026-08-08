<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AchievementBadge extends Model
{
    protected $table = 'achievement_badge';
    protected $primaryKey = 'badge_id';
    public $timestamps = false;

    protected $fillable = [
        'badge_name',
        'description',
        'requirement',
        'badge_image',
        'criteria_type',
        'target_count',
    ];

    protected $casts = [
        'target_count' => 'integer',
    ];

    public function userAchievements(): HasMany
    {
        return $this->hasMany(
            UserAchievement::class,
            'badge_id',
            'badge_id'
        );
    }
}