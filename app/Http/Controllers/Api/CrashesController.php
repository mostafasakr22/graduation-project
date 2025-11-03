<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrashesController extends Controller
{

    // Show All Crashes
    public function index()
    {
        $crashes = Crash::whereHas('vehicle', function($q){
            $q->where('user_id', Auth::id());
        })->latest()->get();

        return response()->json($crashes);
    }

    // Add Crash
    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'crash_time' => 'required|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'severity' => 'nullable|in:low,medium,high',
            'speed_before' => 'nullable|numeric',
            'acceleration_impact' => 'nullable|numeric',
        ]);

        $crash = Crash::create($data);
        return response()->json($crash, 201);
    }
}
