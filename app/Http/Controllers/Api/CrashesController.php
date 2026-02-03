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
            'vehicle_id'   => 'required|exists:vehicles,id',
            'latitude'     => 'required',
            'longitude'    => 'required',
            'severity'     => 'required|in:low,medium,high,critical',
            'speed_before' => 'nullable|numeric',
            'description'  => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data' => $validator->errors()
            ], 422);
        }

        // 2. Black Box Logic: البحث عن الرحلة الحالية لربط الحادثة
        $activeTrip = Trip::where('vehicle_id', $request->vehicle_id)
            ->where('status', 'ongoing')
            ->first();

        // 3. Create Crash
        $crash = Crash::create([
            'vehicle_id'   => $request->vehicle_id,
            'trip_id'      => $activeTrip ? $activeTrip->id : null,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
            'severity'     => $request->severity,
            'speed_before' => $request->speed_before,
            'crashed_at'   => now(),
        ]);

        // 4. Emergency Stop (إنهاء الرحلة إجبارياً)
        if ($activeTrip) {
            $activeTrip->update([
                'status' => 'completed',
                'end_time' => now()
            ]);
        }

        // 5. Send Notification to Owner 
        try {
            // بنجيب بيانات العربية
            $vehicle = Vehicle::find($request->vehicle_id);
            
            if ($vehicle) {
                // محاولة 1: نجيب المالك من السواق
                $ownerId = null;
                if ($vehicle->driver_id) {
                    $driver = Driver::find($vehicle->driver_id);
                    if ($driver) $ownerId = $driver->owner_id;
                }

                // محاولة 2: لو مفيش سواق، ممكن تكون العربية مربوطة بمالك مباشرة (لو عندك user_id في vehicles)
                if (!$ownerId && $vehicle->user_id) {
                    $ownerId = $vehicle->user_id;
                }

                // لو لقينا المالك، نبعتله
                if ($ownerId) {
                    $owner = User::find($ownerId);
                    // لازم نحمل علاقة العربية عشان تظهر في الإشعار
                    $crash->setRelation('vehicle', $vehicle);
                    $owner->notify(new CrashAlert($crash));
                }
            }
        } catch (\Exception $e) {
           Log::error($e->getMessage()); 
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'crash' => $crash,
                'message' => 'Crash reported, linked, and owner notified successfully'
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