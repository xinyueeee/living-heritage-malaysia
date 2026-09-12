<?php

namespace App\Models;

use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'experience_id',
        'community_group_id',
    ];


    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXPERIENCE
    |--------------------------------------------------------------------------
    */

    public function experience(): BelongsTo
    {
        return $this->belongsTo(
            Experience::class,
            'experience_id',
            'experiences_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMMUNITY GROUP
    |--------------------------------------------------------------------------
    */

    public function communityGroup(): BelongsTo
    {
        return $this->belongsTo(
            CommunityGroup::class,
            'community_group_id',
            'group_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LIKES
    |--------------------------------------------------------------------------
    */

    public function likes(): HasMany
    {
        return $this->hasMany(
            PostLike::class,
            'post_id',
            'post_id'
        );
    }


    public function isLikedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()
            ->where('user_id', $user->user_id)
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | COMMENTS
    |--------------------------------------------------------------------------
    */

    public function postComments(): HasMany
    {
        return $this->hasMany(
            PostComment::class,
            'post_id',
            'post_id'
        );
    }
}