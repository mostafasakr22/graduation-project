<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Trip_location;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TripsController extends Controller
{
    // 1. Start Trip
    public function startTrip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id'    => 'required|exists:vehicles,id',
            'driver_id'     => 'required|exists:drivers,id',
            'start_lat'     => 'nullable|string',
            'start_lng'     => 'nullable|string',
            'start_address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // Check if busy
        $existingTrip = Trip::where('vehicle_id', $request->vehicle_id)
                            ->where('status', 'ongoing')
                            ->first();

        if ($existingTrip) {
            return response()->json([
                'status' => 'fail',
                'data' => [
                    'message' => 'Vehicle is already on an ongoing trip',
                    'trip_id' => $existingTrip->id
                ]
            ], 400);
        }

        // Create Trip directly
        $trip = Trip::create([
            'vehicle_id'    => $request->vehicle_id,
            'driver_id'     => $request->driver_id,
            'start_time'    => Carbon::now(),
            'start_lat'     => $request->start_lat,
            'start_lng'     => $request->start_lng,
            'start_address' => $request->start_address,
            'status'        => 'ongoing'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => ['trip' => $trip]
        ], 201);
    }

    // 2. Log Location
    public function logLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'   => 'required|exists:trips,id',
            'latitude'  => 'required',
            'longitude' => 'required',
            'speed'     => 'nullable|numeric',
            'heading'   => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        $trip = Trip::find($request->trip_id);
        
        if (!$trip || $trip->status !== 'ongoing') {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Trip ended or invalid']], 400);
        }

        Trip_location::create($request->all());

        return response()->json(['status' => 'success', 'data' => null]);
    }

    // 3. End Trip
    public function endTrip(Request $request, $id)
    {
        $trip = Trip::find($id);

        if (!$trip || $trip->status !== 'ongoing') {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Trip ended or invalid']], 400);
        }

        $validator = Validator::make($request->all(), [
            'end_lat'     => 'nullable|string',
            'end_lng'     => 'nullable|string',
            'end_address' => 'nullable|string',
            'distance_km' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // Calculations
        $endTime = Carbon::now();
        $startTime = Carbon::parse($trip->start_time);
        $hours = $startTime->diffInMinutes($endTime) / 60;
        
        $avgSpeed = 0;
        if ($hours > 0 && $request->distance_km > 0) {
            $avgSpeed = $request->distance_km / $hours;
        }

        // Get Max Speed form locations
        $maxSpeed = Trip_location::where('trip_id', $trip->id)->max('speed') ?? 0;

        $trip->update([
            'end_time'    => $endTime,
            'end_lat'     => $request->end_lat,
            'end_lng'     => $request->end_lng,
            'end_address' => $request->end_address,
            'distance_km' => $request->distance_km,
            'avg_speed'   => round($avgSpeed, 2),
            'max_speed'   => round($maxSpeed, 2),
            'status'      => 'completed'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => ['trip' => $trip]
        ]);
    }

    // 4. History
    public function getHistory(Request $request)
    {
        $query = Trip::with(['vehicle', 'driver'])->orderBy('created_at', 'desc');

        if ($request->has('driver_id')) $query->where('driver_id', $request->driver_id);
        if ($request->has('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);

        return response()->json([
            'status' => 'success',
            'data' => ['trips' => $query->get()]
        ]);
    }

    // 5. Show
    public function show($id)
    {
        $trip = Trip::with(['vehicle', 'driver', 'locations'])->find($id);

        if (!$trip) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Not found']], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => ['trip' => $trip]
        ]);
    }
}