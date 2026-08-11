<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Experience extends Model
{
    protected $table = 'experiences';

    protected $primaryKey = 'experiences_id';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExperienceType::class, 'type_id', 'type_id');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'favourite',
            'experience_id',
            'user_id',
            'experiences_id',
            'user_id',
        )->withPivot('saved_date');
    }
}
