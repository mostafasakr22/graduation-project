<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Crash;
use App\Models\Trip;
use App\Models\Trip_location;
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
        // 1. جلب أرقام عربيات المالك الحالي 
        $vehicleIds = Vehicle::where('user_id', auth()->id())->pluck('id');

        // 2. جلب الحوادث المرتبطة بالعربيات دي (مع بيانات العربية والرحلة)
        $crashes = Crash::whereIn('vehicle_id', $vehicleIds)
            ->with(['vehicle', 'trip'])
            ->orderBy('crashed_at', 'desc')
            ->get();

        // 3. حساب الإجمالي (Total)
        $totalCrashes = $crashes->count();

        // 4. إرسال الرد (Response)
        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_crashes' => $totalCrashes
                ],
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
            'type' => 'required|in:major_crash,hard_braking,aggressive_turn,road_bump,early_warning,fuel_leak',
            'ax' => 'nullable|numeric',
            'ay' => 'nullable|numeric',
            'az' => 'nullable|numeric',
            'yaw' => 'nullable|numeric',
            'speed_before' => 'nullable|numeric',
            'rpm_before' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // 2. البحث عن الرحلة والمالك
        $activeTrip = Trip::where('vehicle_id', $request->vehicle_id)->where('status', 'ongoing')->first();

        $owner = null;
        $vehicle = Vehicle::with('driver')->find($request->vehicle_id);

        // المحاولة الأولى من اليوزر المرتبط بالعربية
        if ($vehicle->user_id) {
            $owner = User::find($vehicle->user_id);
        }
        // المحاولة الثانية من المالك المرتبط بالسواق
        elseif ($vehicle->driver && $vehicle->driver->owner_id) {
            $owner = User::find($vehicle->driver->owner_id);
        }

        // 3. تحديد الخطورة والإجراءات 
        $severity = 'low';
        $shouldNotify = false;
        $shouldStopTrip = false;

        switch ($request->type) {
            case 'major_crash':
                $severity = 'critical';
                $shouldNotify = true;  // نبعت إشعار SOS
                $shouldStopTrip = true; // نوقف الرحلة
                break;
            case 'early_warning':
            case 'fuel_leak':
                $severity = 'high';
                $shouldNotify = true;  // نبعت إشعار صيانة للمالك
                break;
            case 'hard_braking':
            case 'aggressive_turn':
                $severity = 'medium'; // للتقييم والـ Score بس
                break;
            case 'road_bump':
                $severity = 'low';
                break;
        }

        // 4. حفظ الحدث في الداتابيز
        $crash = Crash::create([
            'vehicle_id' => $request->vehicle_id,
            'trip_id' => $activeTrip ? $activeTrip->id : null,
            'type' => $request->type,
            'severity' => $severity,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'ax' => $request->ax,
            'ay' => $request->ay,
            'az' => $request->az,
            'yaw' => $request->yaw,
            'speed_before' => $request->speed_before,
            'rpm_before' => $request->rpm_before,
            'crashed_at' => now(),
        ]);

        // 5. تنفيذ الإجراءات (Alerts & Stop)
        if ($shouldStopTrip && $activeTrip) {

            // حساب الوقت
            $endTime = now();
            $startTime = \Carbon\Carbon::parse($activeTrip->start_time);
            $hours = $startTime->diffInMinutes($endTime) / 60;

            // حساب المسافة 
            $calculatedDistance = $activeTrip->calculateDistance();

            // السرعة المتوسطة
            $avgSpeed = ($hours > 0 && $calculatedDistance > 0) ? ($calculatedDistance / $hours) : 0;

            // أقصى سرعة 
            $maxSpeed = Trip_location::where('trip_id', $activeTrip->id)->max('speed') ?? 0;

            // استخراج آخر مكان للعربية
            $lastLocation = $activeTrip->locations()->latest()->first();
            $endLat = $lastLocation ? $lastLocation->latitude : $request->latitude;
            $endLng = $lastLocation ? $lastLocation->longitude : $request->longitude;

            // التحديث النهائي للرحلة
            $activeTrip->update([
                'status' => 'completed',
                'end_time' => $endTime,
                'end_lat' => $endLat,
                'end_lng' => $endLng,
                'end_address' => 'Ended by Major Crash',
                'distance_km' => $calculatedDistance,
                'avg_speed' => round($avgSpeed, 2),
                'max_speed' => round($maxSpeed, 2)
            ]);
        }

        $alertTypes = ['major_crash', 'fuel_leak', 'early_warning'];
        $shouldNotify = in_array($crash->type, $alertTypes);

        // إرسال الإشعار (Notification)
        if ($shouldNotify && $owner) {
            // بنجيب بيانات العربية ومعاها بيانات السواق اللي شغال عليها حالياً
            $vehicleWithDriver = Vehicle::with('driver')->find($request->vehicle_id);

            // بنحط البيانات دي جوه كائن الحادثة عشان ملف الإشعار يقدر يقراها
            $crash->setRelation('vehicle', $vehicleWithDriver);

            // لو في رحلة شغالة، بنحطها برضه عشان الإشعار يقرا منها لو احتاج
            if ($activeTrip) {
                $crash->setRelation('trip', $activeTrip);
            }

            // ابعت الإشعار للمالك
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