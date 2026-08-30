<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostComment extends Model
{
    protected $table = 'post_comment';

    protected $primaryKey = 'comment_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_id',
        'comment',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(
            Post::class,
            'post_id',
            'post_id'
        );
    }
}