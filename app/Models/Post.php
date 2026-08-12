<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $table = 'post';

    protected $primaryKey = 'post_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'content',
        'post_images',
        'like_count',
        'comments',
        'saved_users',
        'created_at',
    ];

    /**
     * A post belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
