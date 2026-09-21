<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;

class CheckpointController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Checkpoint::with('trail')->get()
        ]);
    }
}