<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
            'plate_number' => 'required|string|unique:vehicles',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'driver_id' => 'nullable|exists:drivers,id|unique:vehicles,driver_id'
        ]);

        $vehicle = Vehicle::create([
            'user_id' => Auth::id(),
            'plate_number' => $request->plate_number,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'driver_id' => $request->driver_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle added successfully',
            'data' => $vehicle
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
        $vehicle = Vehicle::findOrFail($id);

        // Authorization check
        if ($vehicle->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this vehicle'
            ], 403);
        }

        // Validation
        $validatedData = $request->validate([
            'make' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'plate_number' => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'driver_id' => 'sometimes|nullable|exists:drivers,id|unique:vehicles,driver_id,' . $vehicle->id,
        ]);

        // Update
        $vehicle->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Updated successfully',
            'data' => $vehicle
        ]);
    }

    // Delete vehicle
    public function delete($id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle deleted successfully'
        ]);
    }
}
