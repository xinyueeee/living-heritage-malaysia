<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TripPlanItem;

class TripPlan extends Model
{
    protected $table = 'trip_plans';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id', 
        'trip_name', 'area',
        'trip_date',
        'status',
    ];

    protected $casts = [
        'trip_date' => 'date',
    ];


    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id'
        );
    }


    public function items(): HasMany
    {
        return $this->hasMany(
            TripPlanItem::class,
            'trip_plan_id',
            'id'
        );
    }
}