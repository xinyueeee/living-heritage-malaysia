<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    protected $table = 'album';

    protected $primaryKey = 'album_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'album_name',
        'description',
        'cover_photo_url',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(
            AlbumPhoto::class,
            'album_id',
            'album_id'
        );
    }
}