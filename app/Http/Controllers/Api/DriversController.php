<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
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
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'national_number' => 'required|string|unique:drivers',
            'license_number'  => 'required|string|unique:drivers',
            'email'           => 'required|string|email|unique:drivers',
            'password'        => 'required|string|min:6',
            'phone'           => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data'   => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['password'] = Hash::make($data['password']);
        $data['user_id']  = Auth::id();

        $driver = Driver::create($data);

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver' => $driver
            ]
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
            ], 404);
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
                'data'   => ['message' => 'Driver not found']
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'national_number' => 'sometimes|string|unique:drivers,national_number,' . $driver->id,
            'license_number'  => 'sometimes|string|unique:drivers,license_number,' . $driver->id,
            'email'           => 'sometimes|string|email|unique:drivers,email,' . $driver->id,
            'password'        => 'sometimes|string|min:6',
            'phone'           => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data'   => $validator->errors()
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
            ], 404);
        }

        $driver->delete();

        return response()->json([
            'status' => 'success',
            'data' => ['message' => 'Driver deleted successfully']
        ]);
    }
}
