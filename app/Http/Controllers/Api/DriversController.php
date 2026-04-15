<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Crash;
use App\Models\Trip_location;
use App\Models\Trip;
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
            'name'=> 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        $driverName = $request->name ?? 'Unknown';

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
        // استخدم المسار الكامل للموديل لتجنب الأخطاء
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Driver not found']], 404);
        }

        // 1. تجميع الرحلات
        $tripIds = $driver->trips()->pluck('id');

        if ($tripIds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'driver_name' => $driver->name,
                    'score' => 100, 
                    'rating' => 'Fresh', 
                    'stats' => 'No trips yet'
                ]
            ]);
        }

        // 2. حساب الخصومات 
        $events = Crash::whereIn('trip_id', $tripIds)->get();
        
        $speedingCount = Trip_Location::whereIn('trip_id', $tripIds)
                                                ->where('speed', '>', 120)
                                                ->count();

        $majorCrashes   = $events->where('type', 'major_crash')->count();
        $hardBraking    = $events->where('type', 'hard_braking')->count();
        $aggressiveTurn = $events->where('type', 'aggressive_turn')->count();

        // تجميع الخصم
        $totalPenalty = 0;
        $totalPenalty += ($majorCrashes * 40);
        $totalPenalty += ($hardBraking * 5);
        $totalPenalty += ($aggressiveTurn * 5);
        $totalPenalty += ($speedingCount * 0.5);

        // 3. حساب المكافآت 
        // بنجمع المسافة اللي مشيها في كل الرحلات
        $totalDistance = Trip::whereIn('id', $tripIds)->sum('distance_km');

        // المعادلة: كل 50 كم = +5 نقاط
        $bonusPoints = floor($totalDistance / 50) * 5;

        // 4. المعادلة النهائية (100 - خصم + مكافأة)
        $score = 100 - $totalPenalty + $bonusPoints;

        $score = min(100, max(0, round($score)));

        // 5. التقييم اللفظي
        $rating = 'Excellent';
        if ($score < 90) $rating = 'Good';
        if ($score < 75) $rating = 'Average';
        if ($score < 50) $rating = 'Bad';
        if ($score < 25) $rating = 'Dangerous';

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver_name' => $driver->name,
                'total_trips' => $tripIds->count(),
                'total_distance_km' => round($totalDistance, 2), 
                'overall_score' => $score,
                'rating' => $rating,
                'breakdown' => [
                    'penalties' => [
                        'points_lost' => $totalPenalty,
                        'details' => [
                            'major_crashes' => $majorCrashes,
                            'hard_braking' => $hardBraking,
                            'aggressive_turns' => $aggressiveTurn,
                            'speeding_incidents' => $speedingCount
                        ]
                    ],
                    'bonuses' => [
                        'points_gained' => $bonusPoints,
                        'reason' => 'Safe driving distance (+5 pts / 50km)'
                    ]
                ]
            ]
        ]);
    }
}
