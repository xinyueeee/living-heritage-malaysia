<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityGroupMember extends Model
{
    protected $table = 'community_group_member';

    protected $primaryKey = 'member_id';

    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'user_id',
        'joined_at',
    ];

    /**
     * Group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(
            CommunityGroup::class,
            'group_id',
            'group_id'
        );
    }

    /**
     * User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }
}