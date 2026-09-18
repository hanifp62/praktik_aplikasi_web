<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trail extends Model
{
    protected $fillable = [
        'mountain_id',
        'name',
        'distance_km',
        'elevation_gain_m',
        'estimated_duration_hours',
        'technical_demand',
        'terrain_character',
    ];

    public function mountain()
    {
        return $this->belongsTo(Mountain::class);
    }

    public function checkpoints()
    {
        return $this->hasMany(Checkpoint::class);
    }
}