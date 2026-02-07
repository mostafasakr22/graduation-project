<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Crash;
use App\Models\Trip;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class VehiclesController extends Controller
{
    // Show all vehicles 
    public function index()
    {
        $vehicles = Vehicle::where('user_id', Auth::id())->get();

        return response()->json([
            'status' => 'success',
            'data' => $vehicles
        ]);
    }

    // Add Vehicle
      public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|unique:vehicles',
            'make'         => 'required|string',
            'model'        => 'required|string',
            'year'         => 'nullable|integer|min:1900|max:' . date('Y'),
            'driver_id'    => 'nullable|exists:drivers,id|unique:vehicles,driver_id',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // 1. رفع الصورة (لو موجودة)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('vehicle_images', 'public');
        }

        // 2. create
        $vehicle = Vehicle::create([
            'user_id'      => Auth::id(),
            'plate_number' => $request->plate_number,
            'make'         => $request->make,
            'model'        => $request->model,
            'year'         => $request->year,
            'driver_id'    => $request->driver_id,
            'image'        => $imagePath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle added successfully',
            'data' => ['vehicle' => $vehicle]
        ], 201);
    }


    // Show one vehicle
    public function show($id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $vehicle
        ]);
    }

    // Update vehicle
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Vehicle not found']], 404);
        }

        // Authorization check
        if ($vehicle->user_id != Auth::id()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'You are not authorized to update this vehicle'
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'make'         => 'sometimes|string|max:255',
            'model'        => 'sometimes|string|max:255',
            'plate_number' => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'year'         => 'sometimes|integer|min:1900|max:' . date('Y'),
            'driver_id'    => 'sometimes|nullable|exists:drivers,id|unique:vehicles,driver_id,' . $vehicle->id,
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // استبعاد الصورة من الـ request المباشر
        $data = $request->except(['image']);

        // التعامل مع الصورة الجديدة
        if ($request->hasFile('image')) {
            // أ) مسح القديمة
            if ($vehicle->image) {
                Storage::disk('public')->delete($vehicle->image);
            }
            // ب) رفع الجديدة
            $data['image'] = $request->file('image')->store('vehicle_images', 'public');
        }

        // update
        $vehicle->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Updated successfully',
            'data' => ['vehicle' => $vehicle]
        ]);
    }

    // Delete vehicle
    public function delete($id)
    {
        // 1. البحث عن Vehicle
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(
            ['status' => 'fail', 'data' => ['message' => 'Vehicle not found']], 404);
        }

       
        
        // مسح crash
        Crash::where('vehicle_id', $id)->delete();

        // مسح trips
        Trip::where('vehicle_id', $id)->delete();

        // مسح records
        Record::where('vehicle_id', $id)->delete(); 

        // 3. مسح ال vehicle
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Vehicle deleted successfully'
        ]);
    }

    // دالة عشان السواق يعرف العربية المخصصة ليه
    public function getMyVehicle(Request $request)
    {
        // 1. نجيب اليوزر الحالي (السواق)
        $user = $request->user();

        // 2. نجيب ملفه من جدول السائقين
        $driver = Driver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'status' => 'fail',
                'data' => ['message' => 'Driver profile not found']
            ], 404);
        }

        // 3. ندور على العربية اللي مربوطة بالسواق ده
        $vehicle = Vehicle::where('driver_id', $driver->id)->first();

        if (!$vehicle) {
            return response()->json([
                'status' => 'fail', 
                'data' => ['message' => 'No vehicle assigned to you yet']
            ], 404);
        }

        // 4. نرجع بيانات العربية
        return response()->json([
            'status' => 'success',
            'data' => ['vehicle' => $vehicle]
        ]);
    }
}
