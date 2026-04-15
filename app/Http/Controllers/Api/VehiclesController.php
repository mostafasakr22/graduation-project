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
            'driver_id'    => 'nullable|exists:drivers,id|unique:vehicles,driver_id',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'vehicle_class' => 'required|in:sedan,heavy_duty',
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
            'driver_id'    => $request->driver_id,
            'image'        => $imagePath,
            'vehicle_class' => $request->vehicle_class,
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
            'plate_number' => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'driver_id'    => 'sometimes|nullable|exists:drivers,id|unique:vehicles,driver_id,' . $vehicle->id,
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'vehicle_class' => 'sometimes|in:sedan,heavy_duty',
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
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Vehicle not found']], 404);
        }

        // أمان: تأكد إن المالك هو اللي بيمسح عربيته
        if ($vehicle->user_id != auth()->id()) {
            return response()->json(['status' => 'fail', 'message' => 'Unauthorized'], 403);
        }

        // 1. مسح الحوادث المرتبطة بالعربية (عشان SQL Server ميعملش Error)
        Crash::where('vehicle_id', $id)->delete();

        // 2. مسح الرحلات المرتبطة بالعربية
        Trip::where('vehicle_id', $id)->delete();

        // 4. حذف صورة العربية (لو موجودة) من السيرفر عشان نوفر مساحة
        if ($vehicle->image) {
            Storage::disk('public')->delete($vehicle->image);
        }

        // 5.  مسح العربية نفسها
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Vehicle and all its related history deleted successfully'
        ]);
    }

    
}
