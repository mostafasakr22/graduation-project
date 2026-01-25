<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
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
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email', 
            'password'        => 'required|string|min:6',
            'national_number' => 'required|string|unique:users,national_number',
            'license_number'  => 'required|string|unique:drivers',
            'phone'           => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // 2. إنشاء حساب User (للدخول)
        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'role'            => 'driver',
            'national_number' => $request->national_number,
            'phone_number'    => $request->phone,
        ]);

        // 3. إنشاء ملف Driver (وربطه)
        $driver = Driver::create([
            'user_id'         => $user->id,        // ربط بالحساب الشخصي
            'owner_id'        => auth()->id(),     // ربط بالمالك الحالي
            'name'            => $request->name,
            'national_number' => $request->national_number,
            'license_number'  => $request->license_number,
            'email'           => $request->email,
            'phone'           => $request->phone
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver account created successfully',
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
