<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehiclesController extends Controller
{
    // Show All Vehicles
    public function index()
    {
        $vehicles = Vehicle::where('user_id', Auth::id())->get();
        return response()->json($vehicles);
    }

    // Add Vehicle
   public function store(Request $request)
{
    $request->validate([
        'plate_number' => 'required|string|unique:vehicles',
        'make' => 'required|string',
        'model' => 'required|string',
        'year' => 'nullable|integer|min:1900|max:' . date('Y'),
    ]);

    $vehicle = Vehicle::create([
        'user_id' => $request->user()->id,
        'plate_number' => $request->plate_number,
        'make' => $request->make,
        'model' => $request->model,
        'year' => $request->year,
    ]);

    return response()->json([
        'message' => 'Vehicle added successfully',
        'vehicle' => $vehicle
    ], 201);
}


    // Show One Vehicle
    public function show($id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($vehicle);
    }

    // Update Vehicle
 public function update(Request $request, $id)
{
    
    try{ 
        $vehicle = Vehicle::findOrFail($id);

        if ($vehicle->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update this vehicle'
            ], 403);
        }

        
        $validatedData = $request->validate([
            'make' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'plate_number' => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'year' => 'sometimes|integer|min:1900|max:' . date('Y'),
        ]);

        
        $vehicle->update($validatedData);
        $vehicle->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Updayed Successfully',
            'data' => $vehicle
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while updating',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // Delete Vehicle
    public function delete($id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
