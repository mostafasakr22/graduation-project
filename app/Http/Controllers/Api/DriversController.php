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
        // ... Validation زي ما هو ...
        $validator = Validator::make($request->all(), [
            'name' => 'required', 'email' => 'required|email', 'password' => 'required',
            'national_number' => 'required', 'license_number' => 'required', 'phone' => 'nullable'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        // استخدمنا Transaction عشان لو حصل ايرور يمسح اليوزر وميسيبش داتا بايظة
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // 1. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'driver',
                'national_number' => $request->national_number,
                'phone_number' => $request->phone,
            ]);

            // 2. Create Driver
            $driver = Driver::create([
                'user_id' => $user->id,
                'owner_id' => auth()->id(),
                'name' => $request->name,
                'national_number' => $request->national_number,
                'license_number'  => $request->license_number,
                'email' => $request->email,
                'phone' => $request->phone
            ]);

            \Illuminate\Support\Facades\DB::commit(); // احفظ التغييرات

            return response()->json(['status' => 'success', 'data' => $driver]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack(); // الغي كل حاجة حصلت
            // رجعلي رسالة الخطأ بالظبط
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
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
            ], 422);
        }

        $driver->delete();

        return response()->json([
            'status' => 'success',
            'data' => ['message' => 'Driver deleted successfully']
        ]);
    }
}
