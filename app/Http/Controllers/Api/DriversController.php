<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Crash;
use App\Models\Trip_location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DriversController extends Controller
{

    // Show All Drivers
    public function index()
    {
        $drivers = Driver::all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'drivers' => $drivers
            ]
        ]);
    }

    // Add Driver
    public function store(Request $request)
    {
        // 1. Validation 
        $validator = Validator::make($request->all(), [
            'name'=> 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // 2. إنشاء السائق (بيانات فقط)
        $driver = Driver::create([
            'owner_id'=> auth()->id(),
            'name'    => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver added successfully',
            'data' => ['driver' => $driver]
        ], 201);
    }

    // Show One Driver
    public function show($id)
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json([
                'status' => 'fail',
                'data' => ['message' => 'Driver not found']
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data' => ['driver' => $driver]
        ]);
    }

    // Update Driver
    public function update(Request $request, $id)
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json([
                'status' => 'fail',
                'data' => ['message' => 'Driver not found']
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $driver->update($data);
        $driver->refresh();

        return response()->json([
            'status' => 'success',
            'data' => ['driver' => $driver]
        ]);
    }

    // Delete Driver
    public function delete($id)
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json([
                'status' => 'fail',
                'data' => ['message' => 'Driver not found']
            ], 422);
        }

        $driver->delete();

        return response()->json([
            'status' => 'success',
            'data' => ['message' => 'Driver deleted successfully']
        ]);
    }

    // (Driver Scorecard)
    public function getScore($id)
    {
        $driver =Driver::find($id);

        if (!$driver) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Driver not found']], 404);
        }

        // 1. تجميع البيانات من الرحلات السابقة
        $tripIds = $driver->trips()->pluck('id'); // نجيب كل أرقام رحلاته

        if ($tripIds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'driver_name' => $driver->name,
                    'score' => 100, 
                    'rating' => 'Excellent',
                    'stats' => 'No trips yet'
                ]
            ]);
        }

        // 2. إحصائيات السلوك (من جدول crashes/events)
        $events = Crash::whereIn('trip_id', $tripIds)->get();
        
        $majorCrashes   = $events->where('type', 'major_crash')->count();
        $hardBraking    = $events->where('type', 'hard_braking')->count();
        $aggressiveTurn = $events->where('type', 'aggressive_turn')->count();

        // 3. إحصائيات السرعة (من جدول trip_locations)
        $speedingCount =Trip_Location::whereIn('trip_id', $tripIds)
                                                ->where('speed', '>', 120)
                                                ->count();

        // 4. المعادلة الحسابية (Score Calculation)
        $score = 100;
        $score -= ($majorCrashes * 40);   // خصم كبير للحوادث
        $score -= ($hardBraking * 5);     // خصم متوسط للفرملة
        $score -= ($aggressiveTurn * 5);  // خصم متوسط للانعطاف
        $score -= ($speedingCount * 0.5); // خصم بسيط لكل نقطة سرعة (لأنها بتتكرر كتير)

        // التأكد إن السكور مش بالسالب (أقل حاجة صفر)
        $score = max(0, round($score));

        // 5. التقييم اللفظي
        $rating = 'Excellent';                  // 90-100
        if ($score < 90) $rating = 'Good';      // 75-89
        if ($score < 75) $rating = 'Average';   // 50-74
        if ($score < 50) $rating = 'Bad';       // 25-49
        if ($score < 25) $rating = 'Dangerous'; // 0-24

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver_name' => $driver->name,
                'total_trips' => $tripIds->count(),
                'overall_score' => $score,
                'rating' => $rating,
                'breakdown' => [
                    'major_crashes' => $majorCrashes,
                    'hard_braking_events' => $hardBraking,
                    'aggressive_turns' => $aggressiveTurn,
                    'speeding_incidents' => $speedingCount
                ]
            ]
        ]);
    }
}
