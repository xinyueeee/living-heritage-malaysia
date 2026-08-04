<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PassportStamp extends Model
{
    protected $table = 'passport_stamp';

    protected $primaryKey = 'stamp_id';

    public $timestamps = false;

    protected $fillable = [
        'state',
        'category',
        'stamp_image',
    ];

    public function userPassportStamps(): HasMany
    {
        return $this->hasMany(
            UserPassportStamp::class,
            'stamp_id',
            'stamp_id'
        );
    }
}