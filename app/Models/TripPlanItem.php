<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPlanItem extends Model
{
    protected $table = 'trip_plan_items';

    protected $primaryKey = 'id';

    protected $fillable = [
        'trip_plan_id',
        'experience_id',
        'item_type',
        'display_order',
    ];

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(
            TripPlan::class,
            'trip_plan_id',
            'id'
        );
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(
            Experience::class,
            'experience_id',
            'experiences_id'
        );
    }
}