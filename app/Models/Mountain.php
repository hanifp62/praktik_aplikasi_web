<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mountain extends Model
{
    protected $fillable = [
        'name',
        'elevation_mdpl',
        'region',
    ];

    public function trails()
    {
        return $this->hasMany(Trail::class);
    }
}