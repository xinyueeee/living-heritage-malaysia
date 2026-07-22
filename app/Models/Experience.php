<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $table = 'experiences';

    protected $primaryKey = 'experiences_id';

    public $timestamps = false;
}
