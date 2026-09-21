<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checkpoint extends Model
{
    protected $fillable = [
        'trail_id',
        'name',
        'order_index',
    ];

    public function trail()
    {
        return $this->belongsTo(Trail::class);
    }
}