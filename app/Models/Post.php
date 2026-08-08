<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}