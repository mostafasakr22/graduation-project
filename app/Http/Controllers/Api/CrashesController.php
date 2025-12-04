<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrashesController extends Controller
{
    // Show All Crashes
    public function index()
    {
        $crashes = Crash::with('vehicle')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'crashes' => $crashes
            ]
        ]);
    }

    // Add Crash
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'crash_time' => 'required|date',
            'location' => 'nullable|string|max:255',
            'severity' => 'required|in:low,medium,high',
            'speed_before' => 'nullable|numeric|min:0',
            'acceleration_impact' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data' => $validator->errors()
            ], 422);
        }

        $crash = Crash::create($validator->validated());

        return response()->json([
            'status' => 'success',
            'data' => [
                'crash' => $crash
            ]
        ], 201);
    }

    // Delete Crash
    public function delete($id)
    {
        $crash = Crash::find($id);

        if (!$crash) {
            return response()->json([
                'status' => 'fail',
                'data' => [
                    'message' => 'Crash not found'
                ]
            ], 404);
        }

        $crash->delete();

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'Crash deleted successfully'
            ]
        ]);
    }
}
