<?php

namespace App\Http\Controllers;

use App\Models\Trail;

class TrailController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Trail::with('mountain')->get()
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'data' => Trail::with('checkpoints')
                ->findOrFail($id)
        ]);
    }
}