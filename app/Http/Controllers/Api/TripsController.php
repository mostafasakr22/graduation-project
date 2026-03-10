<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Trip_location; 
use App\Events\LocationUpdated;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TripsController extends Controller
{

    // 1. Log Location & Auto-Start Trip (للهاردوير) 
    public function logLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'latitude'   => 'required',
            'longitude'  => 'required',
            'speed'      => 'nullable|numeric',
            'heading'    => 'nullable|numeric',
            'rpm'        => 'nullable|integer', 
            'altitude'   => 'nullable|numeric', 
            'ax'         => 'nullable|numeric', 
            'ay'         => 'nullable|numeric', 
            'az'         => 'nullable|numeric', 
            'yaw'        => 'nullable|numeric', 
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // البحث عن الرحلة أو إنشاؤها (Auto-Start Logic)
        $trip = Trip::where('vehicle_id', $request->vehicle_id)
                    ->where('status', 'ongoing')
                    ->latest()
                    ->first();

        // لو العربية دايرة ومفيش رحلة، نفتح رحلة جديدة
        if (!$trip) {
            $vehicle = Vehicle::find($request->vehicle_id);
            $trip = Trip::create([
                'vehicle_id'    => $request->vehicle_id,
                'driver_id'     => $vehicle->driver_id, 
                'start_time'    => now(),
                'start_lat'     => $request->latitude,
                'start_lng'     => $request->longitude,
                'start_address' => 'Auto-started by Hardware',
                'status'        => 'ongoing'
            ]);
        }

        // حفظ النقطة بكل التفاصيل
        Trip_location::create([
            'trip_id'   => $trip->id,
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'speed'     => $request->speed,
            'heading'   => $request->heading,
            'rpm'       => $request->rpm,
            'altitude'  => $request->altitude,
            'ax'        => $request->ax,
            'ay'        => $request->ay,
            'az'        => $request->az,
            'yaw'       => $request->yaw,
        ]);

        // الإرسال اللايف (Pusher) 🚀
        $liveData = [
            'trip_id'    => $trip->id,
            'vehicle_id' => $request->vehicle_id,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'speed'      => $request->speed,
            'heading'    => $request->heading,
            'rpm'        => $request->rpm,      
            'altitude'   => $request->altitude  
        ];

        broadcast(new LocationUpdated($liveData));

        return response()->json(['status' => 'success', 'message' => 'Logged & Auto-managed']);
    }


    // 2. Hardware End Trip (إنهاء الرحلة ) 
    public function hardwareEndTrip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id'  => 'required|exists:vehicles,id',
            'end_address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        $trip = Trip::where('vehicle_id', $request->vehicle_id)
                    ->where('status', 'ongoing')
                    ->first();

        if (!$trip) {
            return response()->json(['status' => 'fail', 'message' => 'No active trip found for this vehicle'], 404);
        }

        // 1. حساب الوقت (بالساعات)
        $endTime = Carbon::now();
        $startTime = Carbon::parse($trip->start_time);
        $hours = $startTime->diffInMinutes($endTime) / 60;

        // 2. (حساب المسافة)
        $calculatedDistance = $trip->calculateDistance();

        // 3. حساب السرعة المتوسطة
        $avgSpeed = 0;
        if ($hours > 0 && $calculatedDistance > 0) {
            $avgSpeed = $calculatedDistance / $hours;
        }

        // 4. استخراج أقصى سرعة
        $maxSpeed = Trip_location::where('trip_id', $trip->id)->max('speed') ?? 0;

        // 5. التحديث النهائي للرحلة
        $trip->update([
            'end_time'    => $endTime,
            'end_address' => $request->end_address ?? 'Ended by Hardware',
            'distance_km' => $calculatedDistance, 
            'avg_speed'   => round($avgSpeed, 2),
            'max_speed'   => round($maxSpeed, 2),
            'status'      => 'completed'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Trip ended and calculated successfully',
            'data' => ['trip' => $trip]
        ]);
    }


    // 3. History (سجل الرحلات )
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



    // 4. Show Single Trip
    public function show($id)
    {
        $trip = Trip::with(['vehicle', 'driver', 'locations'])->find($id);

        if (!$trip) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Trip not found']], 404); 
        }

        return response()->json([
            'status' => 'success',
            'data' => ['trip' => $trip]
        ]);
    }
}