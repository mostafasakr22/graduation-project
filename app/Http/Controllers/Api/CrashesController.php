<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crash;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrashesController extends Controller{

    // Show All Crashes 
    public function index()
    {

        $crashes = Crash::with(['vehicle', 'trip'])->orderBy('created_at', 'desc')->get();

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
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'latitude' => 'required', 
            'longitude' => 'required', 
            'severity' => 'required|in:low,medium,high,critical',
            'speed_before' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data' => $validator->errors()
            ], 422);
        }

        // 2. Black Box Logic: Find Active Trip
        // بندور هل العربية دي في رحلة حالياً؟ لو اه نربط الحادثة بيها
        $activeTrip = Trip::where('vehicle_id', $request->vehicle_id)
            ->where('status', 'ongoing')
            ->first();

        // 3. Create Crash
        $crash = Crash::create([
            'vehicle_id' => $request->vehicle_id,
            'trip_id' => $activeTrip ? $activeTrip->id : null, 
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'severity' => $request->severity,
            'crashed_at' => now(),
        ]);

        // 4. (Optional) Emergency Trip End
        // لو الحادثة حصلت، نقفل الرحلة ع طول
        if ($activeTrip) {
            $activeTrip->update([
                'status' => 'completed',
                'end_time' => now()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'crash' => $crash,
                'message' => 'Crash reported and linked successfully'
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
            'data' => null
        ]);
    }
}