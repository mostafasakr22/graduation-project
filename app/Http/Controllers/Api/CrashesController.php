<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\Crash;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Notifications\CrashAlert;

class CrashesController extends Controller
{
    // Show All Crashes
    public function index()
    {
        // بنرجع الحادثة مع بيانات العربية والرحلة
        $crashes = Crash::with(['vehicle', 'trip'])->orderBy('crashed_at', 'desc')->get();

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
            'latitude'   => 'required',
            'longitude'  => 'required',
            'type'       => 'required|in:major_crash,hard_braking,aggressive_turn,road_bump',
            'g_force_x'  => 'nullable|numeric',
            'g_force_y'  => 'nullable|numeric',
            'g_force_z'  => 'nullable|numeric',
            'yaw'        => 'nullable|numeric',
            'speed_before' => 'nullable|numeric',
            'rpm_before'   => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // 2. البحث عن الرحلة والمالك
        $activeTrip = Trip::where('vehicle_id', $request->vehicle_id)->where('status', 'ongoing')->first();
        
        $owner = null;
        $vehicle = Vehicle::with('driver')->find($request->vehicle_id);
        if ($vehicle->user_id) {
            $owner = User::find($vehicle->user_id);
        } elseif ($vehicle->driver && $vehicle->driver->owner_id) {
            $owner = User::find($vehicle->driver->owner_id);
        }

        // 3. تحديد الخطورة والإجراءات
        $severity = 'low';
        $shouldNotify = false;
        $shouldStopTrip = false;

        switch ($request->type) {
            case 'major_crash': 
                $severity = 'critical';
                $shouldNotify = true;
                $shouldStopTrip = true;
                break;
            case 'hard_braking': 
            case 'aggressive_turn':
                $severity = 'medium';
                break;
            case 'road_bump':
                $severity = 'low';
                break;
        }

        // 4. حفظ الحدث في الداتابيز
        $crash = Crash::create([
            'vehicle_id'   => $request->vehicle_id,
            'trip_id'      => $activeTrip ? $activeTrip->id : null,
            'type'         => $request->type,
            'severity'     => $severity,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'g_force_x'    => $request->g_force_x,
            'g_force_y'    => $request->g_force_y,
            'g_force_z'    => $request->g_force_z,
            'yaw'          => $request->yaw,
            'speed_before' => $request->speed_before,
            'rpm_before'   => $request->rpm_before,
            'crashed_at'   => now(),
        ]);

        // 5. تنفيذ الإجراءات (Stop Trip & Notify)
        if ($shouldStopTrip && $activeTrip) {
            $calculatedDistance = $activeTrip->calculateDistance();

            $activeTrip->update([
                'status' => 'completed', 
                'end_time' => now(),
                'distance_km' => $calculatedDistance 
            ]);
        }

        if ($shouldNotify && $owner) {
            $crash->setRelation('vehicle', $vehicle);
            $owner->notify(new CrashAlert($crash)); 
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'crash' => $crash,
                'message' => 'Event logged successfully'
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
                'data' => ['message' => 'Crash not found']
            ], 404);
        }

        $crash->delete();

        return response()->json([
            'status' => 'success',
            'data' => null
        ]);
    }
}