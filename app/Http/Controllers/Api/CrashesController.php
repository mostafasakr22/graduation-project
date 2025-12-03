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
        $crashes = Crash::with('vehicle')->get();

        return response()->json([
            'message' => 'All crashes retrieved successfully',
            'data' => $crashes
        ]);
    }

    // Add Crash
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'crash_time' => 'required|date',
            'location' => 'nullable|string|max:255',
            'severity' => 'required|in:low,medium,high',
            'speed_before' => 'nullable|numeric|min:0',
            'acceleration_impact' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $crash = Crash::create($validated);

        return response()->json([
            'message' => 'Crash record added successfully',
            'data' => $crash
        ], 201);
    }

    // Delete Crash
    public function delete($id)
    {
        $crash = Crash::find($id);

        if (!$crash) {
            return response()->json(['message' => 'Crash not found'], 404);
        }

        $crash->delete();

        return response()->json(['message' => 'Crash deleted successfully']);
    }
}