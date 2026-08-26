<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notification';

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'experience_id',
        'selected_date',
        'notification_type',
        'is_read',
        'scheduled_at',
        'message',
    ];

    protected $casts = [
        'selected_date' => 'date',
        'is_read' => 'boolean',
        'scheduled_at' => 'datetime',
    ];

    // ADD THIS
    public function experience(): BelongsTo
    {
        return $this->belongsTo(
            Experience::class,
            'experience_id',
            'experiences_id'
        );
    }
}