<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackPhoto extends Model
{
    protected $table = 'feedback_photo';
    protected $primaryKey = 'photo_id';
    public $timestamps = false;

    protected $fillable = [
        'feedback_id',
        'file_name',
        'file_path',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id', 'feedback_id');
    }
}
