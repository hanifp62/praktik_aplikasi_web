<?php

namespace App\Http\Controllers;

use App\Models\Mountain;

class MountainController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Mountain::with('trails')->get()
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'data' => Mountain::with('trails.checkpoints')
                ->findOrFail($id)
        ]);
    }
}