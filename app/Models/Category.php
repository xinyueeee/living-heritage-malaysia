<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'category';

    protected $primaryKey = 'category_id';

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExperienceType::class, 'type_id', 'type_id');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class, 'category_id', 'category_id');
    }
}
