<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityGroup extends Model
{
    protected $table = 'community_group';

    protected $primaryKey = 'group_id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'cover_image',
        'created_at',
    ];

    /**
     * Group Members
     */
    public function members(): HasMany
    {
        return $this->hasMany(
            CommunityGroupMember::class,
            'group_id',
            'group_id'
        );
    }

    /**
     * Group Posts
     */
    public function posts(): HasMany
    {
        return $this->hasMany(
            Post::class,
            'community_group_id',
            'group_id'
        );
    }
}