<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlbumPhoto extends Model
{
    protected $table = 'album_photo';

    protected $primaryKey = 'album_photo_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'album_id',
        'photo_url',
        'storage_path',
        'created_at',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(
            Album::class,
            'album_id',
            'album_id'
        );
    }
}