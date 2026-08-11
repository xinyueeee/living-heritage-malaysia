<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    protected $primaryKey = 'notification_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'experience_id',
        'notification_type',
        'is_read',
        'scheduled_at',
        'message',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'scheduled_at' => 'datetime',
    ];
}