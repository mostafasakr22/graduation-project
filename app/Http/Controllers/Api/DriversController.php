<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DriversController extends Controller
{
    // Show All Drivers
    public function index()
    {
        $drivers = Driver::all();
        return response()->json($drivers);
    }

    // Add Driver
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'national_number' => 'required|string|unique:drivers',
            'license_number' => 'required|string|unique:drivers',
            'email' => 'required|string|email|unique:drivers',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string'
        ]);

        $driver = Driver::create([
            'name'            => $request->name,
            'national_number' => $request->national_number,
            'license_number'  => $request->license_number,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'user_id'         => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Driver added successfully',
            'driver' => $driver
        ], 201);
    }

    // Show One Driver
    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return response()->json($driver);
    }

    // Update Driver
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'national_number' => 'sometimes|string|unique:drivers,national_number,' . $driver->id,
            'license_number' => 'sometimes|string|unique:drivers,license_number,' . $driver->id,
            'email' => 'sometimes|string|email|unique:drivers,email,' . $driver->id,
            'password' => 'sometimes|string|min:6',
            'phone' => 'sometimes|string',
        ]);


        if ($request->password) {
            $validatedData['password'] = Hash::make($request->password);
        }

        $driver->update($validatedData);
        $driver->refresh();

        return response()->json([
            'message' => 'Driver updated successfully',
            'data' => $driver
        ], 200);
    }

    // Delete Driver
    public function delete($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();

        return response()->json(['message' => 'Driver deleted successfully']);
    }
}
