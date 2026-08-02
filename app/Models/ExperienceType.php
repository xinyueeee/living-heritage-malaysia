<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperienceType extends Model
{
    protected $table = 'experience_type';

    protected $primaryKey = 'type_id';

    public $timestamps = false;

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'type_id', 'type_id');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class, 'type_id', 'type_id');
    }
}
