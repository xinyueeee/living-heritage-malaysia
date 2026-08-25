<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SavedExperienceCollection extends Model
{
    protected $primaryKey = 'collection_id';
    protected $fillable = ['name', 'normalized_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function savedExperiences(): BelongsToMany
    {
        return $this->belongsToMany(Experience::class, 'favourite', 'collection_id', 'experience_id', 'collection_id', 'experiences_id');
    }
}
